<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities\Less;

use Alchemy\Phrasea\Application;
use Alchemy\BinaryDriver\BinaryInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Filesystem\Filesystem;

class Compiler
{
    private $filesystem;
    private $recess;

    public function __construct(Filesystem $filesystem, BinaryInterface $recess)
    {
        $this->filesystem = $filesystem;
        $this->recess = $recess;
    }

    public static function create(Application $app)
    {
        $binaries = $app['phraseanet.configuration']['binaries'];

        return new self($app['filesystem'], RecessDriver::create($binaries));
    }

    public function compile($target, $files)
    {
        $this->filesystem->mkdir(dirname($target));

        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        $files = new ArrayCollection((array) $files);

        if ($files->forAll(function($file) {
            return is_file($file);
        })) {
            throw new RuntimeException($files . ' does not exists.');
        }

        if (!is_writable(dirname($target))) {
            throw new RuntimeException(realpath(dirname($target)) . ' is not writable.');
        }

        $commands = $files->toArray();
        array_unshift($commands, '--compile');

        try {
            $output = $this->recess->command($commands);
            $this->filesystem->dumpFile($target, $output);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Could not execute recess command.', $e->getCode(), $e);
        }
    }
}
