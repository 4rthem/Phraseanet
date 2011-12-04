<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class appbox extends base
{

  /**
   *
   * @var int
   */
  protected $id;
  /**
   *
   * @var appbox
   */
  protected static $_instance;

  /**
   *
   * constant defining the app type
   */
  const BASE_TYPE = self::APPLICATION_BOX;

  /**
   *
   * @var <type>
   */
  protected $session;
  protected $cache;
  protected $connection;
  protected $registry;
  const CACHE_LIST_BASES = 'list_bases';
  const CACHE_SBAS_IDS = 'sbas_ids';

  /**
   * Singleton pattern
   *
   * @return appbox
   */
  public static function get_instance(registryInterface &$registry = null)
  {
    if (!self::$_instance instanceof self)
    {
      self::$_instance = new self($registry);
    }

    return self::$_instance;
  }

  /**
   * Constructor
   *
   * @return appbox
   */
  protected function __construct(registryInterface $registry=null)
  {
    $this->connection = connection::getPDOConnection();
    if (!$registry)
      $registry = registry::get_instance();
    $this->registry = $registry;
    $this->session = Session_Handler::getInstance($this);

    require dirname(__FILE__) . '/../../config/connexion.inc';

    $this->host = $hostname;
    $this->port = $port;
    $this->user = $user;
    $this->passwd = $password;
    $this->dbname = $dbname;

    return $this;
  }

  /**
   *
   * @param collection $collection
   * @param system_file $pathfile
   * @param string $pic_type
   * @return appbox
   */
  public function write_collection_pic(collection $collection, system_file $pathfile=null, $pic_type)
  {
    if ($pathfile instanceof system_file)
    {
      $mime = $pathfile->get_mime();

      if (!in_array(mb_strtolower($mime), array('image/gif', 'image/png', 'image/jpeg', 'image/jpg', 'image/pjpeg')))
      {
        throw new Exception('Invalid file format');
      }
    }
    if (!in_array($pic_type, array(collection::PIC_LOGO, collection::PIC_WM, collection::PIC_STAMP, collection::PIC_PRESENTATION)))
      throw new Exception('unknown pic_type');

    if ($pic_type == collection::PIC_LOGO)
      $collection->update_logo($pathfile);

    $registry = registry::get_instance();
    $file = $registry->get('GV_RootPath') . 'config/' . $pic_type . '/' . $collection->get_base_id();
    if (is_file($file))
    {
      unlink($file);
    }
    $custom_path = $registry->get('GV_RootPath') . 'www/custom/' . $pic_type . '/';
    if (is_file($custom_path))
    {
      unlink($custom_path);
    }
    if (!is_dir($custom_path))
    {
      system_file::mkdir($custom_path);
    }
    $custom_path.= $collection->get_base_id();

    if (is_null($pathfile))

      return $this;

    $datas = file_get_contents($pathfile->getPathname());
    if (is_null($datas))

      return $this;

    file_put_contents($file, $datas);
    file_put_contents($custom_path, $datas);
    $system_file = new system_file($file);
    $system_file->chmod();
    $system_file = new system_file($custom_path);
    $system_file->chmod();

    return $this;
  }

  /**
   *
   * @param databox $databox
   * @param system_file $pathfile
   * @param <type> $pic_type
   * @return appbox
   */
  public function write_databox_pic(databox $databox, system_file $pathfile=null, $pic_type)
  {

    if ($pathfile instanceof system_file)
    {
      $mime = $pathfile->get_mime();

      if (!in_array(mb_strtolower($mime), array('image/jpeg', 'image/jpg', 'image/pjpeg')))
      {
        throw new Exception('Invalid file format');
      }
    }
    if (!in_array($pic_type, array(databox::PIC_PDF)))
      throw new Exception('unknown pic_type');
    $registry = $databox->get_registry();
    $file = $registry->get('GV_RootPath') . 'config/minilogos/' . $pic_type . '_' . $databox->get_sbas_id();
    if (is_file($file))
    {
      unlink($file);
    }
    $custom_path = $registry->get('GV_RootPath') . 'www/custom/minilogos/';
    if (is_file($custom_path))
    {
      unlink($custom_path);
    }
    if (!is_dir($custom_path))
    {
      system_file::mkdir($custom_path);
    }
    $custom_path.= $pic_type . '_' . $databox->get_sbas_id();

    if (is_null($pathfile))

      return $this;

    $datas = file_get_contents($pathfile->getPathname());
    if (is_null($datas))

      return $this;

    file_put_contents($file, $datas);
    file_put_contents($custom_path, $datas);
    $system_file = new system_file($file);
    $system_file->chmod();
    $system_file = new system_file($custom_path);
    $system_file->chmod();
    $databox->delete_data_from_cache('printLogo');

    return $this;
  }

  /**
   *
   * @param collection $collection
   * @param <type> $ordre
   * @return appbox
   */
  public function set_collection_order(collection $collection, $ordre)
  {
    $sqlupd = "UPDATE bas SET ord = :ordre WHERE base_id = :base_id";
    $stmt = $this->get_connection()->prepare($sqlupd);
    $stmt->execute(array(':ordre' => $ord, ':base_id' => $collection->get_base_id()));
    $stmt->closeCursor();

    return $this;
  }

  /**
   *
   * @param databox $databox
   * @param <type> $boolean
   * @return appbox
   */
  public function set_databox_indexable(databox $databox, $boolean)
  {
    $boolean = !!$boolean;
    $sql = 'UPDATE sbas SET indexable = :indexable WHERE sbas_id = :sbas_id';

    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute(array(
        ':indexable' => ($boolean ? '1' : '0'),
        ':sbas_id' => $databox->get_sbas_id()
    ));
    $stmt->closeCursor();

    return $this;
  }

  /**
   *
   * @param databox $databox
   * @return <type>
   */
  public function is_databox_indexable(databox $databox)
  {
    $sql = 'SELECT indexable FROM sbas WHERE sbas_id = :sbas_id';

    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute(array(':sbas_id' => $databox->get_sbas_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $indexable = $row ? $row['indexable'] : null;

    return $indexable;
  }

  /**
   *
   * @param databox $databox
   * @param <type> $viewname
   * @return appbox
   */
  public function set_databox_viewname(databox $databox, $viewname)
  {
    $viewname = strip_tags($viewname);
    $sql = 'UPDATE sbas SET viewname = :viewname WHERE sbas_id = :sbas_id';

    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute(array(':viewname' => $viewname, ':sbas_id' => $databox->get_sbas_id()));
    $stmt->closeCursor();

    $appbox = appbox::get_instance();
    $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
    cache_databox::update($databox->get_sbas_id(), 'structure');

    return $this;
  }

  /**
   *
   * @return const
   */
  public function get_base_type()
  {
    return self::BASE_TYPE;
  }

  public function forceUpgrade(Setup_Upgrade &$upgrader)
  {
    $upgrader->add_steps(8 + count($this->get_databoxes()));

    $registry = $this->get_registry();

    /**
     * Step 1
     */
    $upgrader->set_current_message(_('Flushing cache'));
    if ($this->get_cache()->ping())
    {
      $this->get_cache()->flush();
    }
    $upgrader->add_steps_complete(1);

    /**
     * Step 2
     */
    $upgrader->set_current_message(_('Purging directories'));
    $system_file = new system_file($registry->get('GV_RootPath') . 'tmp/cache_minify/');
    $system_file->empty_directory();
    $upgrader->add_steps_complete(1);

    /**
     * Step 3
     */
    $upgrader->set_current_message(_('Purging directories'));
    $system_file = new system_file($registry->get('GV_RootPath') . 'tmp/cache_twig/');
    $system_file->empty_directory();
    $upgrader->add_steps_complete(1);

    /**
     * Step 4
     */
    $upgrader->set_current_message(_('Purging directories'));
    if (!is_dir($registry->get('GV_RootPath') . 'www/setup/.svn') && is_dir($registry->get('GV_RootPath') . 'www/setup/'))
    {
      $system_file = new system_file($registry->get('GV_RootPath') . 'www/setup/');
      $system_file->empty_directory();
      rmdir($system_file->getPathname());
    }
    $upgrader->add_steps_complete(1);

    /**
     * Step 5
     */
    $upgrader->set_current_message(_('Copying files'));
    phrasea::copy_custom_files();
    $upgrader->add_steps_complete(1);

    $advices = array();

    /**
     * Step 6
     */
    $upgrader->set_current_message(_('Upgrading appbox'));
    $advices = $this->upgradeDB(true, $upgrader);
    $upgrader->add_steps_complete(1);

    /**
     * Step 7
     */
    foreach ($this->get_databoxes() as $s)
    {
      $upgrader->set_current_message(sprintf(_('Upgrading %s'),$s->get_viewname()));
      $advices = array_merge($advices, $s->upgradeDB(true, $upgrader));
      $upgrader->add_steps_complete(1);
    }

    /**
     * Step 8
     */
    $upgrader->set_current_message(_('Post upgrade'));
    $this->post_upgrade($upgrader);
    $upgrader->add_steps_complete(1);


    /**
     * Step 9
     */
    $upgrader->set_current_message(_('Flushing cache'));
    if ($this->get_cache()->ping())
    {
      $this->get_cache()->flush();
    }
    $upgrader->add_steps_complete(1);

    return $advices;
  }

  protected function post_upgrade(Setup_Upgrade &$upgrader)
  {
    $upgrader->add_steps(1 + count($this->get_databoxes()));
    $this->apply_patches($this->get_version(), GV_version, true, $upgrader);
    $this->setVersion(GV_version);
    $upgrader->add_steps_complete(1);

    foreach ($this->get_databoxes() as $databox)
    {
      $databox->apply_patches($databox->get_version(), GV_version, true, $upgrader);
      $databox->setVersion(GV_version);
      $upgrader->add_steps_complete(1);
    }

    return $this;
  }

  /**
   *
   * @param registryInterface $registry
   * @param type $conn
   * @param type $dbname
   * @param type $write_file
   * @return type
   */
  public static function create(registryInterface &$registry, connection_interface $conn, $dbname, $write_file = false)
  {
    $credentials = $conn->get_credentials();

    if ($conn->is_multi_db() && trim($dbname) === '')
      throw new Exception(_('Nom de base de donnee incorrect'));

    if ($write_file)
    {
      if ($conn->is_multi_db() && !isset($credentials['dbname']))
      {
        $credentials['dbname'] = $dbname;
      }
      $connexion = dirname(__FILE__) . "/../../config/connexion.inc";
      if (is_file($connexion))
        unlink($connexion);

      $EOL = PHP_EOL;

      $connexionINI = '<?php' . $EOL;
      foreach ($credentials as $key => $value)
      {
        $connexionINI .= '$' . $key . ' = "' . str_replace('"', '\"', $value) . '";' . $EOL;
      }

      if (!file_put_contents($connexion, $connexionINI, FILE_APPEND) !== false)
        throw new Exception(sprintf(_('Impossible d\'ecrire dans le dossier %s'), dirname(dirname(__FILE__)) . "/config/"));

      if (function_exists('chmod'))
        chmod($connexion, 0700);
    }
    try
    {
      if ($conn->is_multi_db())
      {
        $conn->query('CREATE DATABASE `' . $dbname . '`
            CHARACTER SET utf8 COLLATE utf8_unicode_ci');
      }
    }
    catch (Exception $e)
    {

    }

    try
    {
      if ($conn->is_multi_db())
      {
        $conn->query('USE `' . $dbname . '`');
      }
    }
    catch (Exception $e)
    {
      throw new Exception(_('setup::la base de donnees existe deja et vous n\'avez pas les droits ou vous n\'avez pas les droits de la creer') . $e->getMessage());
    }

    try
    {
      $appbox = self::get_instance($registry);
      $appbox->insert_datas();
    }
    catch (Exception $e)
    {
      throw new Exception('Error while installing ' . $e->getMessage());
    }

    return $appbox;
  }

  protected $databoxes;

  /**
   *
   * @return Array
   */
  public function get_databoxes()
  {
    if ($this->databoxes)

      return $this->databoxes;

    $ret = array();
    foreach ($this->retrieve_sbas_ids() as $sbas_id)
    {
      try
      {
        $ret[$sbas_id] = databox::get_instance($sbas_id);
      }
      catch (Exception $e)
      {

      }
    }

    $this->databoxes = $ret;

    return $this->databoxes;
  }

  protected function retrieve_sbas_ids()
  {
    try
    {
      return $this->get_data_from_cache(self::CACHE_SBAS_IDS);
    }
    catch (Exception $e)
    {

    }
    $sql = 'SELECT sbas_id FROM sbas';

    $ret = array();

    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $ret[] = (int) $row['sbas_id'];
    }

    $this->set_data_to_cache($ret, self::CACHE_SBAS_IDS);

    return $ret;
  }

  public function get_databox($sbas_id)
  {
    $databoxes = $this->get_databoxes();
    if (!array_key_exists($sbas_id, $databoxes))
      throw new Exception_DataboxNotFound('Databox `' . $sbas_id . '` not found');

    return $databoxes[$sbas_id];
  }

  /**
   *
   * @return Session_Handler
   */
  public function get_session()
  {
    return $this->session;
  }

  public static function list_databox_templates()
  {
    $files = array();
    $dir = new DirectoryIterator(dirname(__FILE__) . '/../conf.d/data_templates/');
    foreach ($dir as $fileinfo)
    {
      if ($fileinfo->isFile())
      {
        $files[] = substr($fileinfo->getFilename(), 0, (strlen($fileinfo->getFilename()) - 4));
      }
    }

    return $files;
  }

  /**
   *
   * @param <type> $option
   * @return string
   */
  public function get_cache_key($option = null)
  {
    return 'appbox_' . ($option ? $option . '_' : '');
  }

}
