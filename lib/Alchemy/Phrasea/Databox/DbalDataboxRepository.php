<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Doctrine\DBAL\Connection;

class DbalDataboxRepository implements DataboxRepositoryInterface
{
    /** @var Connection */
    private $connection;
    /** @var DataboxHydrator */
    private $hydrator;

    public function __construct(Connection $connection, DataboxHydrator $hydrator)
    {
        $this->connection = $connection;
        $this->hydrator = $hydrator;
    }

    /**
     * @param int $id
     * @return \databox|null
     */
    public function find($id)
    {
        $row = $this->fetchRow($id);

        if (is_array($row)) {
            return $this->hydrator->hydrateRow($id, $row);
        }

        return null;
    }

    /**
     * @return \databox[]
     */
    public function findAll()
    {
        return $this->hydrator->hydrateRows($this->fetchRows());
    }

    /**
     * @param int $id
     * @return false|array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRow($id)
    {
        $query = 'SELECT ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas WHERE sbas_id = :id';
        $statement = $this->connection->prepare($query);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $row;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRows()
    {
        $query = 'SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas';
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $rows = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['sbas_id'];
            unset($row['sbas_id']);
            $rows[$id] = $row;
        }
        $statement->closeCursor();

        return $rows;
    }
}
