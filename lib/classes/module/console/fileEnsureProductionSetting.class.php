<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Command\Command;
use Alchemy\Phrasea\Core;
use Symfony\Component\Yaml;

/**
 * @todo write tests
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_console_fileEnsureProductionSetting extends Command
{

  const ALERT = 1;
  const ERROR = 0;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $configuration;
  protected $env;
  protected $testSuite = array(
    'checkPhraseanetScope'
    , 'checkDatabaseScope'
    , 'checkTeamplateEngineService'
    , 'checkOrmService'
    , 'checkCacheService'
    , 'checkOpcodeCacheService'
  );
  protected $connexionOk = false;

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Ensure production settings');

    //$this->addArgument('conf', InputArgument::OPTIONAL, 'The file to check');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    $this->initTests($output);

    $this->prepareTests($output);

    $this->runTests($output);

    $output->writeln('End');
    return 0;
  }

  private function initTests(OutputInterface $output)
  {
    $this->configuration = Core\Configuration::build();

    if (!$this->configuration->isInstalled())
    {
      $output->writeln(sprintf("\nPhraseanet is not installed\n"));

      return 1;
    }
  }

  private function prepareTests(OutputInterface $output)
  {
    try
    {
      $this->checkParse($output);
      $this->checkGetSelectedEnvironement($output);
      $this->checkGetSelectedEnvironementFromFile($output);
    }
    catch (\Exception $e)
    {
      $previous        = $e->getPrevious();
      $previousMessage = $previous instanceof \Exception ? $previous->getMessage() : 'Unknown.';

      $output->writeln(sprintf(
          "<error>Error while loading : %s (%s)</error>"
          , $e->getMessage()
          , $previousMessage
        )
      );

      return 1;
    }
  }

  private function runTests(OutputInterface $output)
  {
    $nbErrors = 0;

    foreach ($this->testSuite as $test)
    {
      $display = "";
      switch ($test)
      {
        case 'checkPhraseanetScope' :
          $display = "Phraseanet Scope Configuration";
          break;
        case 'checkDatabaseScope' :
          $display = "Database configuration & connexion";
          break;
        case 'checkTeamplateEngineService' :
          $display = "Template Engine Service";
          break;
        case 'checkOrmService' :
          $display = "ORM Service";
          break;
        case 'checkCacheService' :
          $display = "Cache Service";
          break;
        case 'checkOpcodeCacheService' :
          $display = "Opcode Cache Service";
          break;
        default:
          throw new \Exception('Unknown test');
          break;
      }

      $output->writeln(sprintf("\n||| %s", mb_strtoupper($display)));

      try
      {
        call_user_func(array($this, $test), $output);
      }
      catch (\Exception $e)
      {
        $nbErrors++;
        $previous = $e->getPrevious();

        $output->writeln(sprintf(
            "<error>%s FAILED : %s</error>"
            , $e->getMessage()
            , $previous instanceof \Exception ? $previous->getMessage() : 'Unknown'
          )
        );
        $output->writeln("");
      }
    }
    if (!$nbErrors)
    {
      $output->writeln("\n<info>Your production settings are setted correctly ! Enjoy</info>");
    }
    else
    {
      $output->writeln("\n<error>Some errors found in your conf</error>");
    }
    return (int) ($nbErrors > 0);
  }

  private function checkParse(OutputInterface $output)
  {

    if (!$this->configuration->getConfigurations())
    {
      throw new \Exception("Unable to load configurations\n");
    }
    if (!$this->configuration->getConnexions())
    {
      throw new \Exception("Unable to load connexions\n");
    }
    if (!$this->configuration->getServices())
    {
      throw new \Exception("Unable to load services\n");
    }

    return;
  }

  private function checkCacheService(OutputInterface $output)
  {
    $cache = $this->configuration->getCache();
    $this->probeCacheService($output, 'MainCache', $cache);
  }

  private function checkOpcodeCacheService(OutputInterface $output)
  {
    $cache = $this->configuration->getOpcodeCache();
    $this->probeCacheService($output, 'MainOpcodeCache', $cache);
  }

  private function checkGetSelectedEnvironement(OutputInterface $output)
  {
    try
    {
      $this->configuration->getConfiguration();
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Error getting configuration\n"), null, $e);
    }

    return;
  }

  private function checkGetSelectedEnvironementFromFile(OutputInterface $output)
  {
    $configuration = Core\Configuration::build();

    try
    {
      $configuration->getConfiguration();
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Error getting environment\n"), null, $e);
    }

    $output->writeln(sprintf("Will Ensure Production Settings on <info>%s</info>", $configuration->getEnvironnement()));
    return;
  }

  private function checkPhraseanetScope(OutputInterface $output)
  {
    try
    {
      $phraseanet = $this->configuration->getPhraseanet();

      foreach($phraseanet->all() as $conf=>$value)
      {
        switch($conf)
        {
          default:
            $this->printConf($output, $conf, $value);
            break;
          case 'servername':
            $url = $value;

            $parseUrl = parse_url($url);

            if (empty($url))
            {
              $message = "<error>should not be empty</error>";
            }
            elseif (!filter_var($url, FILTER_VALIDATE_URL))
            {
              $message = "<error>not valid</error>";
            }
            elseif ($parseUrl["scheme"] !== "https")
            {
              $message = "<comment>should be https</comment>";
            }
            else
            {
              $message = "<info>OK</info>";
            }
            $this->printConf($output, $conf, $value, false, $message);
            break;
          case 'maintenance':
          case 'debug':
          case 'display_errors':
            $message = $value ? '<error>Should be false</error>' : '<info>OK</info>';
            $this->printConf($output, $conf, $value, false, $message);
            break;
        }
      }
//      $this->printConf($output, 'phraseanet', $phraseanet->all());

//      $url = $phraseanet->get("servername");



      if (!$phraseanet->has("debug"))
      {
        $output->writeln(sprintf("<comment>You should give debug a value</comment>", $url));
      }
      elseif ($phraseanet->get("debug") !== false)
      {
        throw new \Exception("phraseanet:debug must be initialized to false");
      }

      if ($phraseanet->get("display_errors") !== false)
      {
        throw new \Exception("Display errors should be false");
      }

      if ($phraseanet->get("maintenance") === true)
      {
        throw new \Exception("phraseanet:warning maintenance is set to false");
      }
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Check Phraseanet Scope\n"), null, $e);
    }
    $output->writeln("");
    $output->writeln("<info>Phraseanet scope is correctly setted</info>");
    $output->writeln("");
    return;
  }

  private function checkDatabaseScope(OutputInterface $output)
  {
    try
    {
      $connexionName = $this->configuration->getPhraseanet()->get('database');
      $connexion     = $this->configuration->getConnexion($connexionName);

      $output->writeln(sprintf("Current connexion is '%s'", $connexionName));
      $output->writeln("");
      foreach ($connexion->all() as $key => $element)
      {
        $output->writeln(sprintf("%s: %s", $key, $element));
      }

      if ($connexion->get("driver") === "pdo_sqlite")
      {
        throw new \Exception("A sqlite database is not recommanded for production environment");
      }

      try
      {
        $config = new \Doctrine\DBAL\Configuration();
        $conn   = \Doctrine\DBAL\DriverManager::getConnection(
            $connexion->all()
            , $config
        );
        unset($conn);
        $this->connexionOk = true;
      }
      catch (\Exception $e)
      {
        throw new \Exception(sprintf(
            "Unable to connect to database declared in connexion '%s' for the following reason %s"
            , $connexionName
            , $e->getMessage()
          )
        );
      }

      $output->writeln("");
      $output->writeln(sprintf("<info>'%s' successfully connect to database</info>", $connexionName));
      $output->writeln("");
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("CHECK Database Scope\n"), null, $e);
    }

    return;
  }

  private function checkTeamplateEngineService(OutputInterface $output)
  {
    try
    {
      $templateEngineName = $this->configuration->getTemplating();
//      $output->writeln(sprintf("Current template engine service is '%s' ", $templateEngineName));
//      $output->writeln("");
      try
      {
        $configuration = $this->configuration->getService($templateEngineName);
        $this->printConf($output, $templateEngineName, $configuration->all());
      }
      catch (\Exception $e)
      {
        $message = sprintf(
          "%s called from %s in %s:template_engine scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , "PROD"
          , $templateEngineName
        );
        $e       = new \Exception($message);
        throw $e;
      }

      $service = Core\Service\Builder::create(
          \bootstrap::getCore()
          , $templateEngineName
          , $configuration
      );

      if ($service->getType() === 'twig')
      {
        $twig = $service->getDriver();

        if ($twig->isDebug())
        {
          throw new \Exception(sprintf("%s service should not be in debug mode", $service->getName()));
        }

        if ($twig->isStrictVariables())
        {
          throw new \Exception(sprintf("%s service should not be set in strict variables mode", $service->getName()));
        }
      }
    }
    catch (\Exception $e)
    {
      if ($e instanceof \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException)
      {
        if ($e->getKey() === 'template_engine')
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s "
                , $e->getKey()
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service"
                , $e->getKey()
                , $templateEngineName
              )
          );
        }
      }

      throw new \Exception(sprintf("Check Template Service\n"), null, $e);
    }
    $output->writeln(sprintf("<info>'%s' template engine service is correctly setted </info>", $templateEngineName));
    $output->writeln("");
    return;
  }

  private function checkOrmService(OutputInterface $output)
  {
    if (!$this->connexionOk)
    {
      $output->writeln("<comment>As ORM service test depends on database test success, it is not executed</comment>");

      return;
    }

    try
    {
      $ormName = $this->configuration->getOrm();

      $output->writeln(sprintf("Current ORM service is '%s'", $ormName));
      $output->writeln("");
      try
      {
        $configuration = $this->configuration->getService($ormName);
        $this->printConf($output, $ormName, $configuration->all());
      }
      catch (\Exception $e)
      {
        $message  = sprintf(
          "%s called from %s in %s scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , $ormName
        );
        $e        = new \Exception($message);
        throw $e;
      }
      $registry = \registry::get_instance();

      $service = Core\Service\Builder::create(
          \bootstrap::getCore()
          , $ormName
          , $configuration
      );

      if ($service->getType() === 'doctrine')
      {
        $output->writeln("");

        $caches = $service->getCacheServices()->all();

        if ($service->isDebug())
        {
          throw new \Exception(sprintf(
              "%s service should not be in debug mode"
              , $service->getName()
            )
          );
        }

        $output->writeln("");

        $options = $configuration->get("options");

        if (!isset($options['orm']['cache']))
        {

          throw new \Exception(sprintf(
              "%s:doctrine:orm:cache must not be empty. In production environment the cache is highly recommanded."
              , $service->getName()
            )
          );
        }

        foreach ($caches as $key => $cache)
        {
          $ServiceName = $options['orm']['cache'][$key];

          $this->probeCacheService($output, $key, $ServiceName);
        }

        try
        {
          $logServiceName = $options['log'];
          $configuration  = $this->configuration->getService($logServiceName);
          $serviceLog     = Core\Service\Builder::create(
              \bootstrap::getCore()
              , $logServiceName
              , $configuration
          );

          $exists = true;
        }
        catch (\Exception $e)
        {
          $exists = false;
        }

        if ($exists)
        {
          throw new \Exception(sprintf(
              "doctrine:orm:log %s service should not be enable"
              , $serviceLog->getName()
            )
          );
        }
      }
    }
    catch (\Exception $e)
    {
      if ($e instanceof \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException)
      {
        if ($e->getKey() === 'orm')
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for service %s"
                , $e->getKey()
                , $service->getName()
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service declared"
                , $e->getKey()
                , $service->getName()
              )
          );
        }
      }

      throw new \Exception(sprintf("Check ORM Service : "), null, $e);
    }

    $output->writeln("");
    $output->writeln(sprintf("<info>'%s' ORM service is correctly setted </info>", $ormName));
    $output->writeln("");

    return;
  }

  protected function probeCacheService(OutputInterface $output, $cacheName, $ServiceName)
  {
    $originalConfiguration = $this->configuration->getService($ServiceName);
    $options               = $originalConfiguration->all();

    if (!empty($options))
    {
      $output->writeln(sprintf("%s cache service", $ServiceName));
      $this->printConf($output, $cacheName . ":" . $ServiceName, $options);
    }

    $Service = Core\Service\Builder::create(
        \bootstrap::getCore(), $ServiceName, $originalConfiguration
    );
    if ($Service->getDriver()->isServer())
    {
      switch ($Service->getType())
      {
        default:
          $output->writeln(sprintf("<error>Unable to check %s</error>", $Service->getType()));
          break;
        case 'memcache':
          if (!memcache_connect($Service->getHost(), $Service->getPort()))
          {
            $output->writeln(
              sprintf(
                "<error>Unable to connect to memcache service %s with host '%s' and port '%s'</error>"
                , $Service->getName()
                , $Service->getHost()
                , $Service->getPort()
              )
            );
          }
          break;
      }
    }
    if ($Service->getType() === 'array')
    {
      $output->writeln(
        sprintf(
          "<error>doctrine:orm:%s %s service should not be an array cache type</error>"
          , $Service->getName()
          , $cacheName
        )
      );
    }
  }

  private function printConf($output, $scope, $value, $scopage = false, $message = '')
  {
    if (is_array($value))
    {
      foreach ($value as $key => $val)
      {
        if ($scopage)
          $key = $scope . ":" . $key;
        $this->printConf($output, $key, $val, $scopage, '');
      }
    }
    elseif (is_bool($value))
    {
      if ($value === false)
      {
        $value = 'false';
      }
      elseif ($value === true)
      {
        $value = 'true';
      }
      $output->writeln(sprintf("\t%s: %s %s", $scope, $value, $message));
    }
    elseif (!empty($value))
    {
      $output->writeln(sprintf("\t%s: %s %s", $scope, $value, $message));
    }
  }

}
