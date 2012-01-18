<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Core as PhraseaCore;
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConfigurationTest extends \PhraseanetPHPUnitAbstract
{

  public function setUp()
  {
    parent::setUp();
  }

  public function testInitialization()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );

    $fileName = __DIR__ . '/confTestFiles/good.yml';

    $spec->expects($this->any())
            ->method('getConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject($fileName)
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);
    $configuration->setEnvironnement('prod');

    $this->assertEquals('prod', $configuration->getEnvironnement());
    $this->assertTrue($configuration->isInstalled());
    $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag'
            , $configuration->getConfiguration()
    );
    $this->assertFalse($configuration->isDebug());
    $this->assertFalse($configuration->isDisplayingErrors());
    $this->assertFalse($configuration->isMaintained());
    $this->assertTrue(is_array($configuration->getPhraseanet()->all()));
  }

  public function testInstalled()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );

    $spec->expects($this->any())
            ->method('getConfigurationFile')
            ->will($this->throwException(new \Exception()));

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);
    $configuration->setEnvironnement('prod');

    $this->assertFalse($configuration->isInstalled());
    try
    {
      $configuration->getPhraseanet();
      $this->fail("should raise an exception because application is not yet installed");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testGetAvailableLogger()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);
    $configuration->setEnvironnement('prod');

    $availableLogger = $configuration->getAvailableDoctrineLogger();

    $this->assertTrue(is_array($availableLogger));
    $this->assertContains('monolog', $availableLogger);
    $this->assertContains('echo', $availableLogger);
  }

  public function testGetHandler()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);
    $configuration->setEnvironnement('prod');

    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration\Handler', $configuration->getConfigurationHandler());
  }

  public function testSetHandler()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);
    $configuration->setEnvironnement('prod');

    $spec2 = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');

    $spec2->expects($this->any())
            ->method('getConfigurationFile')
            ->will(
                    $this->returnValue(
                            'test'
                    )
    );

    $newHandler = new Configuration\Handler($spec2, new Configuration\Parser\Yaml());

    $configuration->setConfigurationHandler($newHandler);

    $this->assertEquals('test', $configuration->getConfigurationHandler()->getSpecification()->getConfigurationFile());
  }

//  public function testBadDoctrineLogger()
//  {
//    $spec = $this->getMock(
//            '\Alchemy\Phrasea\Core\Configuration\Application'
//            , array('getConfigurationFile')
//    );
//
//    $fileName = __DIR__ . '/confTestFiles/bad_doctrine_logger.yml';
//
//    $spec->expects($this->any())
//            ->method('getConfigurationFile')
//            ->will(
//                    $this->returnValue(
//                            new \SplFileObject($fileName)
//                    )
//    );
//
//    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());
//
//    $configuration = new PhraseaCore\Configuration($handler);
//    $configuration->setEnvironnement('prod');
//    
//    try
//    {
//      $configuration->getDoctrine();
//      $this->fail('An exception should be raised');
//    }
//    catch(Exception $e)
//    {
//      
//    }
//  }
}