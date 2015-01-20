<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class NullQueryNode extends Node
{
    public function buildQuery(QueryContext $context)
    {
        return array('match_all' => array());
    }

    public function getTextNodes()
    {
        return array();
    }

    public function __toString()
    {
        return '<NULL>';
    }
}
