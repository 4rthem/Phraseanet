<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;
use MediaAlchemyst\Transmuter\Image2Image;

class SubdefsJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('task::subdef:creation des sous definitions');
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Subdefs';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("task::subdef:creation des sous definitions des documents d'origine");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new SubdefsEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());
        $thumbnailExtraction = (Boolean) (string) $settings->embedded;

        Image2Image::$lookForEmbeddedPreview = $thumbnailExtraction;

        $sqlqmark = array();
        $sqlparms = array();
        foreach(array('image',
                    'video',
                    'audio',
                    'document',
                    'flash',
                    'unknown') as $type) {
            if (!isset($settings->{"type_" . $type}) || !\p4field::isno($settings->{"type_" . $type})) {
                $sqlqmark[] = '?';
                $sqlparms[] = $type;
            }
        }
        if(count($sqlqmark) == 0) {
            return;
        }

        foreach ($app->getDataboxes() as $databox) {
            if (!$this->isStarted()) {
                break;
            }

            if(count($settings->xpath("sbas[text()=".$databox->get_sbas_id() ."]")) == 0) {
                continue;
            }

            $conn = $databox->get_connection();

            $sql = 'SELECT coll_id, record_id FROM record'
                . ' WHERE jeton & ' . PhraseaTokens::MAKE_SUBDEF . ' > 0'
                . ' AND type IN(' . implode(',', $sqlqmark) . ')'
                . ' ORDER BY record_id DESC LIMIT 0, 30';
            $stmt = $conn->prepare($sql);
            $stmt->execute($sqlparms);
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $i = 0;
            foreach ($rs as $row) {
                if (!$this->isStarted()) {
                    break;
                }
                $this->log('info', sprintf("Generate subdefs for : sbasid=%s / databox=%s / recordid=%s ", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id']));

                try {
                    $record = $databox->get_record($row['record_id']);
                    $app['subdef.generator']->generateSubdefs($record);
                } catch (\Exception $e) {
                    $this->log('warning', sprintf("Generate subdefs failed for : sbasid=%s / databox=%s / recordid=%s : %s", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id'], $e->getMessage()));
                }

                $sql = 'UPDATE record'
                    . ' SET jeton=(jeton & ~' . PhraseaTokens::MAKE_SUBDEF . '), moddate=NOW()'
                    . ' WHERE record_id=:record_id';

                $stmt = $conn->prepare($sql);
                $stmt->execute([':record_id' => $row['record_id']]);
                $stmt->closeCursor();

                // rewrite metadata
                $sql = 'UPDATE record'
                    . ' SET jeton=(jeton | ' . PhraseaTokens::WRITE_META_SUBDEF | PhraseaTokens::TO_INDEX . ')'
                    . ' WHERE record_id=:record_id';
                $stmt = $conn->prepare($sql);
                $stmt->execute([':record_id' => $row['record_id']]);
                $stmt->closeCursor();

                unset($record);
                $i++;

                if ($i % 5 === 0) {
                    $app['elasticsearch.indexer']->flushQueue();
                }
            }
        }

        $app['elasticsearch.indexer']->flushQueue();
    }

}
