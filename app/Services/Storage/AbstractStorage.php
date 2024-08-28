<?php

namespace SevereHeadache\Coffre\Services\Storage;

use InvalidArgumentException;

abstract class AbstractStorage implements StorageInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function validatePath(string $path): void
    {
        if (strlen($path) === 0) {
            throw new InvalidArgumentException('Empty');
        } elseif (str_contains($path, '.')) {
            throw new InvalidArgumentException('Contains invalid characters');
        }
    }
}
