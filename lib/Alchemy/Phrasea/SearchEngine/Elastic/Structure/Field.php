<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper as ThesaurusHelper;
use Assert\Assertion;
use databox_field;

/**
 * @todo Field labels
 */
class Field
{
    private $name;
    private $type;
    private $is_searchable;
    private $is_private;
    private $is_facet;
    private $thesaurus_roots;

    public static function createFromLegacyField(databox_field $field)
    {
        $type = self::getTypeFromLegacy($field);

        // Thesaurus concept inference
        $xpath = $field->get_tbranch();
        if ($type === Mapping::TYPE_STRING && !empty($xpath)) {
            $databox = $field->get_databox();
            $roots = ThesaurusHelper::findConceptsByXPath($databox, $xpath);
        } else {
            $roots = null;
        }

        return new self($field->get_name(), $type, [
            'searchable' => $field->is_indexable(),
            'private' => $field->isBusiness(),
            'facet' => $field->isAggregable(),
            'thesaurus_roots' => $roots
        ]);
    }

    private static function getTypeFromLegacy(databox_field $field)
    {
        $type = $field->get_type();
        switch ($type) {
            case databox_field::TYPE_DATE:
                return Mapping::TYPE_DATE;
            case databox_field::TYPE_NUMBER:
                return Mapping::TYPE_DOUBLE;
            case databox_field::TYPE_STRING:
            case databox_field::TYPE_TEXT:
                return Mapping::TYPE_STRING;
            default:
                throw new Exception(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $type));
        }
    }

    public function __construct($name, $type, array $options = [])
    {
        $this->name = (string) $name;
        $this->type = $type;
        $this->is_searchable   = \igorw\get_in($options, ['searchable'], true);
        $this->is_private      = \igorw\get_in($options, ['private'], false);
        $this->is_facet        = \igorw\get_in($options, ['facet'], false);
        $this->thesaurus_roots = \igorw\get_in($options, ['thesaurus_roots'], null);

        Assertion::boolean($this->is_searchable);
        Assertion::boolean($this->is_private);
        Assertion::boolean($this->is_facet);
        if ($this->thesaurus_roots !== null) {
            Assertion::allIsInstanceOf($this->thesaurus_roots, Concept::class);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIndexFieldName()
    {
        return sprintf(
            '%scaption.%s',
            $this->is_private ? 'private_' : '',
            $this->name
        );
    }

    public function getType()
    {
        return $this->type;
    }

    public function isSearchable()
    {
        return $this->is_searchable;
    }

    public function isPrivate()
    {
        return $this->is_private;
    }

    public function isFacet()
    {
        return $this->is_facet;
    }

    public function hasConceptInference()
    {
        return $this->thesaurus_roots !== null;
    }

    public function getThesaurusRoots()
    {
        return $this->thesaurus_roots;
    }

    /**
     * Merge with another field, returning the new instance
     *
     * @param Field $other
     * @return Field
     * @throws MergeException
     */
    public function mergeWith(Field $other)
    {
        if (($name = $other->getName()) !== $this->name) {
            throw new MergeException(sprintf("Fields have different names (%s vs %s)", $this->name, $name));
        }

        // Since mapping is merged between databoxes, two fields may
        // have conflicting names. Indexing is the same for a given
        // type so we reject only those with different types.

        if (($type = $other->getType()) !== $this->type) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible types (%s vs %s)", $name, $type, $this->type));
        }

        if ($other->isPrivate() !== $this->is_private) {
            throw new MergeException(sprintf("Field %s can't be merged, could not mix private and public fields with same name", $name));
        }

        if ($other->isSearchable() !== $this->is_searchable) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible searchablility", $name));
        }

        if ($other->isFacet() !== $this->is_facet) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible facet eligibility", $name));
        }

        $thesaurus_roots = null;
        if ($this->thesaurus_roots !== null || $other->thesaurus_roots !== null) {
            $thesaurus_roots = array_merge(
                (array) $this->thesaurus_roots,
                (array) $other->thesaurus_roots
            );
        }

        return new self($this->name, $this->type, [
            'searchable' => $this->is_searchable,
            'private' => $this->is_private,
            'facet' => $this->is_facet,
            'thesaurus_roots' => $thesaurus_roots
        ]);
    }
}
