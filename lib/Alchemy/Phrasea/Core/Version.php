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
    private $number = '4.0.0-alpha.2';
    private $name = 'Herrerasaurus';

    public function getNumber()
    {
        return $this->number;
    }

    public function getName()
    {
        return $this->name;
    }
}
