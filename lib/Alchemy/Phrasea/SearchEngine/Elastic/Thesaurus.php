<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Filter;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;
use Elasticsearch\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class Thesaurus
{
    private $client;
    private $index;
    private $logger;

    const MIN_SCORE = 4;

    public function __construct(Client $client, $index, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->index = $index;
        $this->logger = $logger;
    }

    /**
     * Find concepts linked to a bulk of Terms
     *
     * @param  Term[]|string[]      $terms  Term objects or strings
     * @param  string|null          $lang   Input language
     * @param  Filter[]|Filter|null $filter Single filter or a filter for each term
     * @param  boolean              $strict Strict mode matching
     * @return Concept[][]                  List of matching concepts for each term
     */
    public function findConceptsBulk(array $terms, $lang = null, $filter = null, $strict = false)
    {
        $this->logger->debug(sprintf('Finding linked concepts in bulk for %d terms', count($terms)));

        // We use the same filter for all terms when a single one is given
        $filters = is_array($filter)
            ? $filter
            : array_fill_keys(array_keys($terms), $filter);
        if (array_diff_key($terms, $filters)) {
            throw new InvalidArgumentException('Filters list must contain a filter for each term');
        }

        // TODO Use bulk queries for performance
        $concepts = array();
        foreach ($terms as $index => $term) {
            $concepts[] = $this->findConcepts($term, $lang, $filters[$index], $strict);
        }

        return $concepts;
    }

    /**
     * Find concepts linked to the provided Term
     *
     * In strict mode, term context matching is enforced:
     *   `orange (color)` will *not* match `orange` in the index
     *
     * @param  Term|string $term   Term object or a string containing term's value
     * @param  string|null $lang   Input language ("fr", "en", ...) for more effective results
     * @param  Filter|null $filter Filter to restrict search on a specified subset
     * @param  boolean     $strict Whether to enable strict search or not
     * @return Concept[]           Matching concepts
     */
    public function findConcepts($term, $lang = null, Filter $filter = null, $strict = false)
    {
        if (!($term instanceof TermInterface)) {
            $term = new Term($term);
        }

        $this->logger->info(sprintf('Searching for term %s', $term), array(
            'strict' => $strict,
            'lang' => $lang
        ));

        if ($strict) {
            $field_suffix = '.strict';
        } elseif ($lang) {
            $field_suffix = sprintf('.%s', $lang);
        } else {
            $field_suffix = '';
        }

        $field = sprintf('value%s', $field_suffix);
        $query = array();
        $query['match'][$field]['query'] = $term->getValue();
        $query['match'][$field]['operator'] = 'and';
        // Allow 25% of non-matching tokens
        // (not exactly the same that 75% of matching tokens)
        // $query['match'][$field]['minimum_should_match'] = '-25%';

        if ($term->hasContext()) {
            $value_query = $query;
            $field = sprintf('context%s', $field_suffix);
            $context_query = array();
            $context_query['match'][$field]['query'] = $term->getContext();
            $context_query['match'][$field]['operator'] = 'and';
            $query = array();
            $query['bool']['must'][0] = $value_query;
            $query['bool']['must'][1] = $context_query;
        } elseif ($strict) {
            $context_filter = array();
            $context_filter['missing']['field'] = 'context';
            $query = self::applyQueryFilter($query, $context_filter);
        }

        if ($lang) {
            $lang_filter = array();
            $lang_filter['term']['lang'] = $lang;
            $query = self::applyQueryFilter($query, $lang_filter);
        }

        if ($filter) {
            $this->logger->debug('Using filter', array('filter' => Filter::dumpPaths($filter)));
            $query = self::applyQueryFilter($query, $filter->getQueryFilter());
        }

        // Path deduplication
        $aggs = array();
        $aggs['dedup']['terms']['field'] = 'path.raw';

        // Search request
        $params = array();
        $params['index'] = $this->index;
        $params['type'] = TermIndexer::TYPE_NAME;
        $params['body']['query'] = $query;
        $params['body']['aggs'] = $aggs;
        // Arbitrary score low limit, we need find a more granular way to remove
        // inexact concepts.
        // We also need to disable TF/IDF on terms, and try to boost score only
        // when the search match nearly all tokens of term's value field.
        $params['body']['min_score'] = self::MIN_SCORE;
        // No need to get any hits since we extract data from aggs
        $params['body']['size'] = 0;

        $this->logger->debug('Sending search', $params['body']);
        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = array();
        $buckets = \igorw\get_in($response, ['aggregations', 'dedup', 'buckets'], []);
        $keys = array();
        foreach ($buckets as $bucket) {
            if (isset($bucket['key'])) {
                $keys[] = $bucket['key'];
                $concepts[] = new Concept($bucket['key']);
            }
        }

        $this->logger->info(sprintf('Found %d matching concepts', count($concepts)),
            array('concepts' => $keys)
        );

        return $concepts;
    }

    private static function applyQueryFilter(array $query, array $filter)
    {
        if (!isset($query['filtered'])) {
            // Wrap in a filtered query
            $filtered = array();
            $filtered['filtered']['query'] = $query;
            $filtered['filtered']['filter'] = $filter;

            return $filtered;
        } elseif (isset($query['filtered']['filter'])) {
            // Reuse the existing filtered query
            if (!isset($query['filtered']['filter']['bool']['must'])) {
                // Wrap the previous filter in a boolean (must) filter
                $previous_filter = $query['filtered']['filter'];
                $query['filtered']['filter'] = array();
                $query['filtered']['filter']['bool']['must'][0] = $previous_filter;
            }
            $query['filtered']['filter']['bool']['must'][] = $filter;

            return $query;
        } else {
            $query['filtered']['filter'] = $filter;

            return $query;
        }
    }
}
