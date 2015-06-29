<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

/**
 * @todo Check for private fields and only search on them if allowed
 */
class QueryContext
{
    /** @var Structure */
    private $structure;
    /** @var array */
    private $locales;
    /** @var string */
    private $queryLocale;
    /** @var array */
    private $fields;

    public function __construct(Structure $structure, array $locales, $queryLocale, array $fields = null)
    {
        $this->structure = $structure;
        $this->locales = $locales;
        $this->queryLocale = $queryLocale;
        $this->fields = $fields;
    }

    public function narrowToFields(array $fields)
    {
        if (is_array($this->fields)) {
            // Ensure we are not escaping from original fields restrictions
            $fields = array_intersect($this->fields, $fields);
            if (!$fields) {
                throw new QueryException('Query narrowed to non available fields');
            }
        }

        return new static($this->structure, $this->locales, $this->queryLocale, $fields);
    }

    public function getRawFields()
    {
        if ($this->fields === null) {
            return array(
                'caption_all.raw',
                'private_caption_all.raw'
            );
        }

        $fields = array();
        foreach ($this->fields as $name) {
            $field = $this->normalizeField($name);
            if (empty($field)) {
                continue;
            }
            $fields[] = sprintf('%s.raw', $field);
        }

        return $fields;
    }

    public function getLocalizedFields()
    {
        if ($this->fields === null) {
            return array_merge(
                $this->localizeField('caption_all'),
                $this->localizeField('private_caption_all')
            );
        }

        $fields = array();
        foreach ($this->fields as $field) {
            $normalized = $this->normalizeField($field);
            foreach ($this->localizeField($normalized) as $fields[]);
        }

        return $fields;
    }

    private function localizeField($field)
    {
        $fields = array();
        foreach ($this->locales as $locale) {
            $boost = ($locale === $this->queryLocale) ? '^5' : '';
            $fields[] = sprintf('%s.%s%s', $field, $locale, $boost);
        }
        // TODO Put generic analyzers on main field instead of "light" sub-field
        $fields[] = sprintf('%s.light^10', $field);

        return $fields;
    }

    /**
     * Returns normalized name or null
     *
     * @param string $name
     * @return null|string
     */
    public function normalizeField($name)
    {
        $field = $this->structure->get($name);
        if (!$field) {
            return null;
        }
        // TODO Field label dereferencing (we only want names)
        return $field->getIndexFieldName();
    }

    public function getFields()
    {
        return $this->fields;
    }
}
