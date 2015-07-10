<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Media;

use Assert\Assertion;

final class ArrayTechnicalDataSet implements \IteratorAggregate, TechnicalDataSet
{
    /** @var TechnicalData[] */
    private $data;

    /**
     * @param TechnicalData[] $data
     */
    public function __construct($data = [])
    {
        Assertion::allIsInstanceOf($data, TechnicalData::class);

        $this->data = [];

        foreach ($data as $technicalData) {
            $this->data[$technicalData->getName()] = $technicalData;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        if ($offset instanceof TechnicalData) {
            $offset = $offset->getName();
        }

        return isset($this->data[$offset]) || array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @param string $offset
     * @param TechnicalData $value
     */
    public function offsetSet($offset, $value)
    {
        Assertion::isInstanceOf($value, TechnicalData::class);

        $name = $value->getName();
        if (null !== $offset) {
            Assertion::eq($name, $offset);
        }

        $this->data[$name] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($offset instanceof TechnicalData) {
            $offset = $offset->getName();
        }

        unset($this->data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getValues()
    {
        $values = [];
        foreach ($this->data as $key => $value) {
            $values[$key] = $value->getValue();
        }

        return $values;
    }

    public function isEmpty()
    {
        return empty($this->data);
    }
}
