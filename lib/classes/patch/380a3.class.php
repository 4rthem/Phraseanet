<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_380a3 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return true;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base $databox, Application $app)
    {
        $conn = $databox->get_connection();
        $conn->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        // Count total rows from `log` table to process
        $stmt = $conn->prepare('
	        SELECT COUNT(l.id) as nb_row
        	FROM log l
        	LEFT JOIN log_colls lc ON (lc.log_id = l.id)
        	WHERE (lc.log_id IS NULL)');
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        $remainingRowsToProcess = (int) $row['nb_row'];
        $failedRows = array();

        do {
            unset($conn, $stmt);
            $conn = $databox->get_connection();
            // Fetch all missing rows from `log_colls` table
            $sql = 'SELECT l.id, l.coll_list FROM log l LEFT JOIN log_colls lc ON (lc.log_id = l.id) WHERE (lc.log_id IS NULL) LIMIT 100';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);

            $sql = 'INSERT INTO log_colls (id, log_id, coll_id) VALUES (null, :log_id, :coll_id)';
            $stmt = $conn->prepare($sql);


            $transaction = false;
            foreach ($rs as $row) {
               // Clean fetched coll ids

                $collIds = array_filter(array_map(function($collId) {
                        return (int) $collId;
                    }, explode(',', (string) $row['coll_list'])), function($collId) {
                    return $collId > 0;
                });
                // Start mysql transaction to avoid case where only a part of coll ids are inserted in `log_colls`

                if(!$transaction){
                    $conn->beginTransaction();
                    $transaction = true;
                }

                try {
                    // For each collection id insert a new row
                    foreach ($collIds as $collId) {
                        $stmt->execute(array(
                            ':log_id'  => (int) $row['id'],
                            ':coll_id' => $collId
                        ));
                    }
                } catch (\Exception $e) {
                    // Rollback if something failed
                    $failedRows[] = $row['id'];
                    $conn->rollBack();
                    $transaction = false;
                    // Go to next row
                    continue;
                }

                unset($collIds);
                // Once all collection ids inserted commit
                $remainingRowsToProcess--;
                $i++;
            }

            $conn->commit();
            $transaction = false;
            $stmt->closeCursor();
        } while (count($failedRows) !== $remainingRowsToProcess);

        unset($conn, $stmt);

        return count($failedRows) === 0;
    }
}