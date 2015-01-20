<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

class Version
{
    protected static $number = '3.8.6-alpha.4';
    protected static $name = 'Falcarius';

    public static function getNumber()
    {
        return static::$number;
    }

    public static function getName()
    {
        return static::$name;
    }
}
