<?php

namespace SevereHeadache\Coffre\Services\Storage;

class StorageFactory
{
    public static function create(string $type): StorageInterface
    {
        switch ($type) {
            case 'file':
                return new FileStorage();
            case 'database':
                return new DatabaseStorage();
            default:
                throw new \RuntimeException();
        }
    }
}
