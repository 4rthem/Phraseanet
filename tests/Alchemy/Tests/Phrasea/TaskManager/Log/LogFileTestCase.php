<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\LogFileInterface;
use Symfony\Component\Finder\Finder;

abstract class LogFileTestCase extends \PhraseanetTestCase
{
    protected $root;

    public function setUp()
    {
        parent::setUp();
        $this->root = __DIR__ . '/root';
        if (!is_dir($this->root)) {
            mkdir($this->root);
        }
    }

    public function tearDown()
    {
        $finder = new Finder();
        $finder->files()->in($this->root);

        foreach ($finder as $file) {
            unlink($file->getRealPath());
        }

        parent::tearDown();
    }

    public function testGetPathIsInRoot()
    {
        $log = $this->getLogFile($this->root);
        $this->assertSame(0, strpos($log->getPath(), $this->root));
    }

    public function testGetContent()
    {
        $log = $this->getLogFile($this->root);
        file_put_contents($log->getPath(), 'hello world');
        $this->assertSame('hello world', $log->getContent());
    }

    public function testGetEmptyContent()
    {
        $log = $this->getLogFile($this->root);
        $this->assertSame('', $log->getContent());
    }

    public function testGetContentStream()
    {
        $log = $this->getLogFile($this->root);
        $this->assertInstanceOf('Closure', $log->getContentStream());
    }

    public function testClear()
    {
        $log = $this->getLogFile($this->root);
        file_put_contents($log->getPath(), 'hello world');
        $log->clear();
        $matches = [];
        $this->assertEquals(1, preg_match('/^\[(.*)\] File cleared/', $log->getContent(), $matches));
        $this->assertDateAtom($matches[1]);
    }

    /**
     * @return LogFileInterface
     */
    abstract protected function getLogFile($root);
}
