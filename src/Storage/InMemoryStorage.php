<?php

namespace Dnoegel\DatabaseInspection\Storage;

/**
 * Class InMemoryStorage
 *
 * @package Dnoegel\DatabaseInspection\Storage
 */
class InMemoryStorage implements Storage
{

    protected $storage = [];

    public function addDocument($entity, $identifier, $document)
    {
        $this->storage[$entity][$identifier] = $document;
    }

    public function hasDocument($entity, $identifier)
    {
        return isset($this->storage[$entity], $this->storage[$entity][$identifier]);
    }

    public function getDocument($entity, $identifier)
    {
        if (!$this->hasDocument($entity, $identifier)) {
            throw new \RuntimeException("Not found: $entity/$identifier");
        }

        return $this->storage[$entity][$identifier];
    }


    public function listDocuments($entity)
    {
        if (!isset($this->storage[$entity])) {
            return [];
        }

        return $this->storage[$entity];
    }

    public function dump()
    {
        return $this->storage;
    }

}