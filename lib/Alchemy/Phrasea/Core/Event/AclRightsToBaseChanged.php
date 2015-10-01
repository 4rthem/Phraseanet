<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclRightsToBaseChanged extends AclRelated
{
    public function getBaseId()
    {
        return $this->args['base_id'];
    }

    public function getRights()
    {
        return $this->args['rights'];
    }
}
