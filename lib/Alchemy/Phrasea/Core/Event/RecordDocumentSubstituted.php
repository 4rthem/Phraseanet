<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;
use record_adapter;


class RecordDocumentSubstituted extends SfEvent
{
    /** @var  record_adapter */
    private $record;

    public function __construct(record_adapter $record)
    {
        $this->record = $record;
    }

    public function getRecord()
    {
        return $this->record;
    }
}
