<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclMasksOnBaseChanged extends AclRelated
{
    public function getBaseId()
    {
        return $this->args['base_id'];
    }
}