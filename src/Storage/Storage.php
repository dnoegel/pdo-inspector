<?php

namespace Dnoegel\DatabaseInspection\Storage;

/**
 * Interface Storage represents very simple storage engine for logging. You probably don't want to use the existing
 * PDO connection of your application to prevent recursion
 *
 * @package Dnoegel\DatabaseInspection\Storage
 */
interface Storage
{
    public function addDocument($entity, $identifier, $document);

    public function listDocuments($entity);

    public function hasDocument($entity, $identifier);

    public function getDocument($entity, $identifier);

}