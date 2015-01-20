<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class FieldNode extends Node
{
    protected $keyword;

    public function __construct($keyword)
    {
        $this->keyword = $keyword;
    }

    public function getValue()
    {
        return $this->keyword;
    }

    public function getQuery()
    {
        throw new \LogicException("A keyword can't be converted to a query.");
    }

    public function getTextNodes()
    {
        throw new \LogicException("A keyword can't contain text nodes.");
    }

    public function __toString()
    {
        return sprintf('<%s>', $this->keyword);
    }

    public function isFullTextOnly()
    {
        return false;
    }
}
