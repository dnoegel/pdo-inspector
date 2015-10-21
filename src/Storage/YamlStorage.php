<?php

namespace Dnoegel\DatabaseInspection\Storage;
use Symfony\Component\Yaml\Yaml;

/**
 * Class JsonStorage is a simple, file based, document storage backend.
 *
 * @package Dnoegel\DatabaseInspection\Storage
 */
class YamlStorage implements Storage
{
    /**
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, '/');
    }

    private function makePath($parts, $create = true)
    {
        $path = implode('/', array_merge([$this->path], $parts, ['']));
        if ($create && !file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;

    }

    public function addDocument($entity, $identifier, $document)
    {
        file_put_contents(
            $this->makePath([$entity]) . $identifier . '.yaml',
            yaml::dump($document)
        );
    }

    public function hasDocument($entity, $identifier)
    {
        return file_exists($this->makePath([$entity], false) . $identifier . '.yaml');
    }

    public function getDocument($entity, $identifier)
    {
        if (!$this->hasDocument($entity, $identifier)) {
            throw new \RuntimeException("Not found: $entity/$identifier");
        }

        return file_get_contents($this->makePath([$entity], false) . $identifier . '.yaml');
    }

    public function listDocuments($entity)
    {
        return str_replace('.yaml', '', str_replace($this->makePath([$entity], false), '', glob($this->makePath([$entity], false) . '*')));
    }
}