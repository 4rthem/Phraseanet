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
 * Kernel
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

namespace Phrasea;

require_once __DIR__ . '/../vendor/Silex/vendor/pimple/lib/Pimple.php';

class Kernel extends \Pimple
{

  public function __construct()
  {
    //autoload
    $this->bootstrap();

    /**
     * load Gatekeeper
     */
    $this['gatekeeper'] = $this->share(function()
            {
              return new \gatekeeper();
            });


    /**
     * Load Gatekeeper
     */
    $this['dispatcher'] = $this->share(function ()
            {
              $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
            });

    /**
     * Initialize Request
     */
    $this['request'] = $this->share(function()
            {
              $app['request'] = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
            });
            
  }

  protected function phraseaAutoload($class_name)
  {
    if (file_exists(__DIR__ .'/../../config/classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __DIR__ .'/../../config/classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }
    elseif (file_exists(__DIR__ . '/../classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __DIR__ . '/../classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }
  }

  protected function bootstrap()
  {
    require_once __DIR__ . '/../vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
    
    $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
    
    spl_autoload_register(array($this, 'phraseaAutoload'));
    
    $loader->registerNamespaces(array(
        'Phrasea' => __DIR__. '/../classes',
    ));
  }

}
