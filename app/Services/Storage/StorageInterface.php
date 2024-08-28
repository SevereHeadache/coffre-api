<?php

namespace SevereHeadache\Coffre\Services\Storage;

use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;

interface StorageInterface
{
    /**
     * Get document tree.
     *
     * @return array{
     *                  name: string,
     *                  path: string,
     *                  children: array|unset,
     *              }
     */
    public function getAll(): array;

    /**
     * Get document contents.
     *
     * @throws StorageNotFoundException If the file doesn't exist
     */
    public function contents(string $path): string;

    /**
     * Save document.
     *
     * @throws StorageExistsException If overwriting existing document
     *                                with $overwrite === false
     */
    public function save(string $path, string $content, bool $overwrite = false): void;

    /**
     * Rename document.
     *
     * @throws StorageNotFoundException If the document doesn't exist
     * @throws StorageExistsException   If overwriting existing document
     */
    public function rename(string $path, string $newName): void;

    /**
     * Delete document.
     *
     * @throws StorageNotFoundException If the document doesn't exist
     */
    public function delete(string $path): void;
}
