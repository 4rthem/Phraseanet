<?php

namespace Alchemy\Phrasea\Collection;

interface CollectionReferenceRepository
{
    /**
     * @return CollectionReference[]
     */
    public function findAll();

    /**
     * @param int $databoxId
     * @return CollectionReference[]
     */
    public function findAllByDatabox($databoxId);

    /**
     * @param int $baseId
     * @return CollectionReference|null
     */
    public function find($baseId);

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return CollectionReference|null
     */
    public function findByCollectionId($databoxId, $collectionId);
}
