<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceCollection;
use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Assert\Assertion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use databox_descriptionStructure;

class SearchEngineOptions
{
    const RECORD_RECORD = 0;
    const RECORD_GROUPING = 1;
    const RECORD_STORY = 2;
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_FLASH = 'flash';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_ALL = '';
    const SORT_RELEVANCE = 'relevance';
    const SORT_CREATED_ON = 'created_on';
    const SORT_RANDOM = 'random';
    const SORT_MODE_ASC = 'asc';
    const SORT_MODE_DESC = 'desc';

    /** @var DbalCollectionReferenceRepository $dbalCollectionReferenceRepository */
    private $collectionReferenceRepository;

    /** @var string */
    protected $record_type = self::TYPE_ALL;

    protected $search_type =  self::RECORD_RECORD;

    /** @var null|int[]  bases ids where searching is done */
    private $basesIds = null;

    /** @var  null|CollectionReference[][] */
    private $collectionsReferencesByDatabox = null;

    /** @var null|\int[] */
    private $databoxesIds;
    /** @var \databox_field[] */

    protected $fields = [];
    protected $status = [];
    /** @var \DateTime */
    protected $date_min;
    /** @var \DateTime */
    protected $date_max;
    protected $date_fields = [];
    /** @var string */
    protected $i18n;
    /** @var bool */
    protected $stemming = true;
    /** @var bool */
    protected $use_truncation = false;
    /** @var string */
    protected $sort_by;

    /** @var string */
    protected $sort_ord = self::SORT_MODE_DESC;

    /** @var int[] */
    protected $business_fields = [];

    /**
     * @var int
     */
    private $max_results = 10;

    /**
     * @var int
     */
    private $first_result = 0;

    private static $serializable_properties = [
        'record_type',
        'search_type',
        'basesIds',
        'fields',
        'status',
        'date_min',
        'date_max',
        'date_fields',
        'i18n',
        'stemming',
        'sort_by',
        'sort_ord',
        'business_fields',
        'max_results',
        'first_result',
        'use_truncation',
    ];

    /**
     * @param Application $app
     * @return callable[]
     */
    private static function getHydrateMethods(Application $app)
    {
        $fieldNormalizer = function ($value) use ($app) {
            return array_map(function ($serialized) use ($app) {
                $data = explode('_', $serialized, 2);

                return $app->findDataboxById($data[0])->get_meta_structure()->get_element($data[1]);
            }, $value);
        };

        $collectionNormalizer = function ($value) use ($app) {
            $references = new CollectionReferenceCollection($app['repo.collection-references']->findMany($value));

            $collections = [];

            foreach ($references->groupByDataboxIdAndCollectionId() as $databoxId => $indexes) {
                /** @var CollectionRepository $repository */
                $repository = $app['repo.collections-registry']->getRepositoryByDatabox($databoxId);

                foreach ($indexes as $collectionId => $index) {
                    $coll = $repository->find($collectionId);
                    $collections[$coll->get_base_id()] = $coll;
                }
            }

            return $collections;
        };

        $optionSetter = function ($setter) {
            return function ($value, SearchEngineOptions $options) use ($setter) {
                $options->{$setter}($value);
            };
        };

        return [
            'record_type' => $optionSetter('setRecordType'),
            'search_type' => $optionSetter('setSearchType'),
            'status' => $optionSetter('setStatus'),
            'date_min' => function ($value, SearchEngineOptions $options) {
                $options->setMinDate($value ? \DateTime::createFromFormat(DATE_ATOM, $value) : null);
            },
            'date_max' => function ($value, SearchEngineOptions $options) {
                $options->setMaxDate($value ? \DateTime::createFromFormat(DATE_ATOM, $value) : null);
            },
            'i18n' => function ($value, SearchEngineOptions $options) {
                if ($value) {
                    $options->setLocale($value);
                }
            },
            'stemming' => $optionSetter('setStemming'),
            'use_truncation' => $optionSetter('setUseTruncation'),
            'date_fields' => function ($value, SearchEngineOptions $options) use ($fieldNormalizer) {
                $options->setDateFields($fieldNormalizer($value));
            },
            'fields' => function ($value, SearchEngineOptions $options) use ($fieldNormalizer) {
                $options->setFields($fieldNormalizer($value));
            },
            'basessIds' => function ($value, SearchEngineOptions $options) {
                $options->onBasesIds($value);
            },
            'business_fields' => function ($value, SearchEngineOptions $options) use ($collectionNormalizer) {
                $options->allowBusinessFieldsOn($collectionNormalizer($value));
            },
            'first_result' => $optionSetter('setFirstResult'),
            'max_results' => $optionSetter('setMaxResults'),
        ];
    }

    /**
     * Defines locale code to use for query
     *
     * @param string $locale An i18n locale code
     * @return $this
     */
    public function setLocale($locale)
    {
        if ($locale && !preg_match('/[a-z]{2,3}/', $locale)) {
            throw new \InvalidArgumentException('Locale must be a valid i18n code');
        }

        $this->i18n = $locale;

        return $this;
    }

    /**
     * Returns the locale value
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->i18n;
    }

    /**
     * @param  string $sort_by
     * @param  string $sort_ord
     * @return $this
     */
    public function setSort($sort_by, $sort_ord = self::SORT_MODE_DESC)
    {
        $this->sort_by = $sort_by;
        $this->sort_ord = $sort_ord;

        return $this;
    }

    /**
     * Allows business fields query on the given bases
     *
     * @param int[] $basesIds
     * @return $this
     */
    public function allowBusinessFieldsOn(array $basesIds)
    {
        $this->business_fields = $basesIds;

        return $this;
    }

    /**
     * Reset business fields settings
     *
     * @return $this
     */
    public function disallowBusinessFields()
    {
        $this->business_fields = [];

        return $this;
    }

    /**
     * Returns an array of bases ids on which business fields are allowed to
     * search on
     *
     * @return int[]
     */
    public function getBusinessFieldsOn()
    {
        return $this->business_fields;
    }

    /**
     * Returns the sort criteria
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Returns the sort order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sort_ord;
    }

    /**
     * Tells whether to use stemming or not
     *
     * @param  boolean             $boolean
     * @return $this
     */
    public function setStemming($boolean)
    {
        $this->stemming = !!$boolean;

        return $this;
    }

    /**
     * Tells whether to use truncation or not
     *
     * @param  boolean             $boolean
     * @return $this
     */
    public function setUseTruncation($boolean)
    {
        $this->use_truncation = !!$boolean;

        return $this;
    }

    /**
     * Return wheter the use of truncation is enabled or not
     *
     * @return boolean
     */
    public function useTruncation()
    {
        return $this->use_truncation;
    }

    /**
     * Return wheter the use of stemming is enabled or not
     *
     * @return boolean
     */
    public function isStemmed()
    {
        return $this->stemming;
    }

    /**
     * Set document type to search for
     *
     * @param  int                 $search_type
     * @return $this
     */
    public function setSearchType($search_type)
    {
        switch ($search_type) {
            case self::RECORD_RECORD:
            default:
                $this->search_type = self::RECORD_RECORD;
                break;
            case self::RECORD_GROUPING:
            case self::RECORD_STORY:
                $this->search_type = self::RECORD_GROUPING;
                break;
        }

        return $this;
    }

    /**
     * Returns the type of documents type to search for
     *
     * @return int
     */
    public function getSearchType()
    {
        return $this->search_type;
    }

    /**
     * Set the bases where to search for
     *
     * @param  int[] $basesIds An array of ids
     * @return $this
     */
    public function onBasesIds(array $basesIds)
    {
        $this->basesIds = $basesIds;

        // Defer databox retrieval
        $this->databoxesIds = null;

        return $this;
    }

    /**
     * Returns the bases ids on which the search occurs
     *
     * @return int[]
     */
    public function getBasesIds()
    {
        if($this->basesIds === null) {
            throw new \LogicException('onBasesIds() must be called before getBasesIds()');
        }

        return $this->basesIds;
    }

    /**
     * @param \databox_field[] $fields An array of Databox fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param  array $status
     * @return $this
     */
    public function setStatus(array $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param  string $record_type
     * @return $this
     */
    public function setRecordType($record_type)
    {
        switch ($record_type) {
            case self::TYPE_ALL:
            default:
                $this->record_type = self::TYPE_ALL;
                break;
            case self::TYPE_AUDIO:
                $this->record_type = self::TYPE_AUDIO;
                break;
            case self::TYPE_VIDEO:
                $this->record_type = self::TYPE_VIDEO;
                break;
            case self::TYPE_DOCUMENT:
                $this->record_type = self::TYPE_DOCUMENT;
                break;
            case self::TYPE_FLASH:
                $this->record_type = self::TYPE_FLASH;
                break;
            case self::TYPE_IMAGE:
                $this->record_type = self::TYPE_IMAGE;
                break;
            case self::TYPE_UNKNOWN:
                $this->record_type = self::TYPE_UNKNOWN;
                break;
        }

        return $this;
    }

    /** @return string */
    public function getRecordType()
    {
        return $this->record_type;
    }

    /**
     * @return $this
     */
    public function setMinDate(\DateTime $min_date = null)
    {
        if ($min_date && $this->date_max && $min_date > $this->date_max) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_min = $min_date;

        return $this;
    }

    /** @return \DateTime
     */
    public function getMinDate()
    {
        return $this->date_min;
    }

    /**
     * @param \DateTime|string $max_date
     * @return $this
     */
    public function setMaxDate(\DateTime $max_date = null)
    {
        if ($max_date && $this->date_max && $max_date < $this->date_min) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_max = $max_date;

        return $this;
    }

    /** @return \DateTime */
    public function getMaxDate()
    {
        return $this->date_max;
    }

    /**
     * @param \databox_field[] $fields
     * @return $this
     */
    public function setDateFields(array $fields)
    {
        $this->date_fields = $fields;

        return $this;
    }

    /** @return \databox_field[] */
    public function getDateFields()
    {
        return $this->date_fields;
    }

    public function serialize()
    {
        $ret = [];
        foreach (self::$serializable_properties as $key) {
            $value = $this->{$key};
            if ($value instanceof \DateTime) {
                $value = $value->format(DATE_ATOM);
            }
            if (in_array($key, ['date_fields', 'fields'])) {
                $value = array_map(function (\databox_field $field) {
                    return $field->get_databox()->get_sbas_id() . '_' . $field->get_id();
                }, $value);
            }
            if ($key == 'business_fields') {
                $value = array_map(function (\collection $collection) {
                    return $collection->get_base_id();
                }, $value);
            }

            $ret[$key] = $value;
        }

        return \p4string::jsonencode($ret);
    }

    /**
     *
     * @param Application $app
     * @param string      $serialized
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function hydrate(Application $app, $serialized)
    {
        $serialized = json_decode($serialized, true);

        if (!is_array($serialized)) {
            throw new \InvalidArgumentException('SearchEngineOptions data are corrupted');
        }

        $options = new static();
        $options->disallowBusinessFields();

        $methods = self::getHydrateMethods($app);

        $sort_by = null;
        $methods['sort_by'] = function ($value) use (&$sort_by) {
            $sort_by = $value;
        };

        $sort_ord = null;
        $methods['sort_ord'] = function ($value) use (&$sort_ord) {
            $sort_ord = $value;
        };

        foreach ($serialized as $key => $value) {
            if (!isset($methods[$key])) {
                throw new \RuntimeException(sprintf('Unable to handle key `%s`', $key));
            }

            if ($value instanceof \stdClass) {
                $value = (array)$value;
            }

            $callable = $methods[$key];

            $callable($value, $options);
        }

        if ($sort_by) {
            if ($sort_ord) {
                $options->setSort($sort_by, $sort_ord);
            } else {
                $options->setSort($sort_by);
            }
        }

        return $options;
    }


    /**
     * Creates options based on a Symfony Request object
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return static
     */
    public static function fromRequest(Application $app, Request $request)
    {
        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        /** @var Authenticator $authenticator */
        $authenticator = $app->getAuthenticator();
        $isAuthenticated = $authenticator->isAuthenticated();

        /** @var ACLProvider $aclProvider */
        $aclProvider = $app['acl'];
        $acl = $isAuthenticated ? $aclProvider->get($authenticator->getUser()) : null;
        if (!$acl) {
            throw new BadRequestHttpException('Not authentified');
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);
        $options = new static();

        $options->collectionReferenceRepository = $app['repo.collection-references'];

        $options->disallowBusinessFields();
        $options->setLocale($app['locale']);

        $options->setSearchType($request->get('search_type'));
        $options->setRecordType($request->get('record_type'));
        $options->setSort($request->get('sort'), $request->get('ord', SearchEngineOptions::SORT_MODE_DESC));
        $options->setStemming((Boolean) $request->get('stemme'));

        $min_date = $max_date = null;
        if ($request->get('date_min')) {
            $min_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->get('date_min') . ' 00:00:00');
        }
        if ($request->get('date_max')) {
            $max_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->get('date_max') . ' 23:59:59');
        }
        $options->setMinDate($min_date);
        $options->setMaxDate($max_date);

        $status = is_array($request->get('status')) ? $request->get('status') : [];
        $options->setStatus($status);

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        $searchableBaseIds = $acl->getSearchableBasesIds();

        $selected_bases = $request->get('bases');
        if (is_array($selected_bases)) {
            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

            $searchableBaseIds = array_intersect($searchableBaseIds, $selected_bases);

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        } else {
            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        if (empty($searchableBaseIds)) {
            throw new BadRequestHttpException('No collections match your criteria');
        }

        $options->onBasesIds($searchableBaseIds);

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        if ($acl->has_right(\ACL::CANMODIFRECORD)) {

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

            /** @var int[] $bf */
            $bf = array_filter($searchableBaseIds, function ($baseId) use ($acl) {
                return $acl->has_right_on_base($baseId, \ACL::CANMODIFRECORD);
            });

            $options->allowBusinessFieldsOn($bf);
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        /** @var \databox[] $databoxes */
        $databoxes = [];
        foreach($options->getCollectionsReferencesByDatabox() as $sbid=>$refs) {
            $databoxes[] = $app->findDataboxById($sbid);
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        $queryFields = is_array($request->get('fields')) ? $request->get('fields') : [];
        if (empty($queryFields)) {
            // Select all fields (business included)
            foreach ($databoxes as $databox) {
                foreach ($databox->get_meta_structure() as $field) {
                    $queryFields[] = $field->get_name();
                }
            }
        }
        $queryFields = array_unique($queryFields);

        $queryDateFields = array_unique(explode('|', $request->get('date_field')));

        $databoxFields = [];
        $databoxDateFields = [];

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        foreach ($databoxes as $databox) {

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

            $metaStructure = $databox->get_meta_structure();

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

            foreach ($queryFields as $fieldName) {
                try {
                    if( ($databoxField = $metaStructure->get_element_by_name($fieldName, databox_descriptionStructure::STRICT_COMPARE)) ) {
                        $databoxFields[] = $databoxField;
                    }
                } catch (\Exception $e) {
                    // no-op
                }
            }

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

            foreach ($queryDateFields as $fieldName) {
                try {
                    if( ($databoxField = $metaStructure->get_element_by_name($fieldName, databox_descriptionStructure::STRICT_COMPARE)) ) {
                        $databoxDateFields[] = $databoxField;
                    }
                } catch (\Exception $e) {
                    // no-op
                }
            }

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        $options->setFields($databoxFields);
        $options->setDateFields($databoxDateFields);

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);

        return $options;
    }

    /**
     * @deprecated
     */
    public function dead_getCollectionsRefences()
    {

    }

    public function getCollectionsReferencesByDatabox()
    {
        if($this->collectionsReferencesByDatabox === null) {
            $this->collectionsReferencesByDatabox = [];
            $refs = $this->collectionReferenceRepository->findMany($this->getBasesIds());
            foreach($refs as $ref) {
                $sbid = $ref->getDataboxId();
                if(!array_key_exists($sbid, $this->collectionsReferencesByDatabox)) {
                    $this->collectionsReferencesByDatabox[$sbid] = [];
                }
                $this->collectionsReferencesByDatabox[$sbid][] = $ref;
            }
        }

        return $this->collectionsReferencesByDatabox;
    }

    public function setMaxResults($max_results)
    {
        Assertion::greaterOrEqualThan($max_results, 0);

        $this->max_results = (int)$max_results;
    }

    public function getMaxResults()
    {
        return $this->max_results;
    }

    /**
     * @param int $first_result
     * @return void
     */
    public function setFirstResult($first_result)
    {
        Assertion::greaterOrEqualThan($first_result, 0);

        $this->first_result = (int)$first_result;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->first_result;
    }
}
