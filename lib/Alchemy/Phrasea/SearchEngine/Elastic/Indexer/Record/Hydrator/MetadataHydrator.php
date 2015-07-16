<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\Utilities\StringHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use DomainException;

class MetadataHydrator implements HydratorInterface
{
    private $connection;
    private $structure;
    private $helper;

    public function __construct(DriverConnection $connection, Structure $structure, RecordHelper $helper)
    {
        $this->connection = $connection;
        $this->structure = $structure;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = <<<SQL
            (SELECT record_id, ms.name AS `key`, m.value AS value, 'caption' AS type, ms.business AS private
            FROM metadatas AS m
            INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)
            WHERE record_id IN (?))

            UNION

            (SELECT record_id, t.name AS `key`, t.value AS value, 'exif' AS type, 0 AS private
            FROM technical_datas AS t
            WHERE record_id IN (?))
SQL;

        $ids = array_keys($records);
        $statement = $this->connection->executeQuery(
            $sql,
            array($ids, $ids),
            array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY)
        );

        while ($metadata = $statement->fetch()) {
            // Store metadata value
            $key = $metadata['key'];
            $value = $metadata['value'];

            // Do not keep empty values
            if (empty($key) || empty($value)) {
                continue;
            }

            $id = $metadata['record_id'];
            if (isset($records[$id])) {
                $record =& $records[$id];
            } else {
                throw new Exception('Received metadata from unexpected record');
            }

            switch ($metadata['type']) {
                case 'caption':
                    // Sanitize fields
                    $value = StringHelper::crlfNormalize($value);
                    switch ($this->structure->typeOf($key)) {
                        case Mapping::TYPE_DATE:
                            $value = $this->helper->sanitizeDate($value);
                            break;

                        case Mapping::TYPE_FLOAT:
                        case Mapping::TYPE_DOUBLE:
                            $value = (float) $value;
                            break;

                        case Mapping::TYPE_INTEGER:
                        case Mapping::TYPE_LONG:
                        case Mapping::TYPE_SHORT:
                        case Mapping::TYPE_BYTE:
                            $value = (int) $value;
                            break;
                    }
                    // Private caption fields are kept apart
                    $type = $metadata['private'] ? 'private_caption' : 'caption';
                    // Caption are multi-valued
                    if (!isset($record[$type][$key])) {
                        $record[$type][$key] = array();
                    }
                    $record[$type][$key][] = $value;
                    // Collect value in the "all" field
                    $field = sprintf('%s_all', $type);
                    if (!isset($record[$field])) {
                        $record[$field] = array();
                    }
                    $record[$field][] = $value;
                    break;

                case 'exif':
                    // EXIF data is single-valued
                    $record['exif'][$key] = $value;
                    break;

                default:
                    throw new Exception('Unexpected metadata type');
                    break;
            }
        }
    }
}
