<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TwigTest extends PhraseanetPHPUnitAbstract
{

  protected $options;

  public function setUp()
  {
    parent::setUp();
    $this->options = array();
  }

  public function testScope()
  {
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                   self::$core, 'hello', $this->options
    );

    $this->assertEquals("template_engine", $doctrine->getScope());
  }

  public function testService()
  {
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                   self::$core, 'hello', $this->options
    );

    $this->assertInstanceOf("\Twig_Environment", $doctrine->getDriver());
  }

  public function testServiceExcpetion()
  {
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                   self::$core, 'hello', $this->options
    );

    $this->assertInstanceOf("\Twig_Environment", $doctrine->getDriver());
  }

  public function testType()
  {
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                   self::$core, 'hello', $this->options
    );

    $this->assertEquals("twig", $doctrine->getType());
  }

}
