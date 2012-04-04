<?php

use DoctrineExtensions\Paginate\Paginate;

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
class patch_361 implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.6.1';

  /**
   *
   * @var Array
   */
  private $concern = array(base::APPLICATION_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return false;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$appbox)
  {
    $Core = \bootstrap::getCore();

    $em = $Core->getEntityManager();

    $conn = $appbox->get_connection();

    $sql    = 'SELECT sbas_id, record_id, id FROM BasketElements';
    $stmt   = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $count = count($result);

    foreach ($result as $row)
    {
      $sbas_id = (int) $row['sbas_id'];

      try
      {
        $connbas = connection::getPDOConnection($sbas_id);
      }
      catch (\Exception $e)
      {
        $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
        $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
        continue;
      }

      $sql  = 'SELECT record_id FROM record WHERE record_id = :record_id';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':record_id' => $row['record_id']));
      $rowCount    = $stmt->rowCount();
      $stmt->closeCursor();

      if ($rowCount == 0)
      {
        $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
        $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
      }
    }


    $dql = "SELECT b FROM Entities\Basket b WHERE b.description != ''";

    $query = $em->createQuery($dql);

    $count = Paginate::getTotalQueryResults($query);

    $n       = 0;
    $perPage = 100;

    while ($n < $count)
    {
      $paginateQuery = Paginate::getPaginateQuery($query, $n, $perPage);

      $result = $paginateQuery->getResult();

      foreach ($result as $basket)
      {
        $htmlDesc = $basket->getDescription();

        $description = trim(strip_tags(str_replace("<br />", "\n", $htmlDesc)));

        if ($htmlDesc == $description)
        {
          continue;
        }

        $basket->setDescription($description);
      }

      $n += $perPage;
      $em->flush();
    }

    $em->flush();


    return true;
  }

}


