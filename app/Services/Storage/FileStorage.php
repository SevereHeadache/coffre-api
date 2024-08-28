<?php

namespace SevereHeadache\Coffre\Services\Storage;

use Illuminate\Support\Facades\Storage;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;

/**
 * File storage operations service.
 */
class FileStorage extends AbstractStorage
{
    /**
     * Translate aliased path to real path.
     */
    private function resolvePath(string $path): string
    {
        $path = explode('/', $path);
        if (count($path) > 1) {
            $name = array_pop($path);
            $path = array_map(fn($part) => '.'.$part, $path);
            array_push($path, $name);
        }

        return implode('/', $path);
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array
    {
        return $this->getFiles();
    }

    private function getFiles(string $path = '', string $alias = ''): array
    {
        $files = Storage::files($path);
        $data = [];
        foreach ($files as $file) {
            $item = [
                'name' => $filename = basename($file),
                'path' => $alias.$filename,
            ];
            $dir = dirname($file).DIRECTORY_SEPARATOR.'.'.$filename;
            if (Storage::directoryExists($dir)) {
                $item['children'] = $this->getFiles(
                    $dir,
                    $alias.$filename.DIRECTORY_SEPARATOR,
                );
            }
            $data[$filename] = $item;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function contents(string $path): string
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        if (Storage::fileMissing($path)) {
            throw new StorageNotFoundException();
        }

        return Storage::read($path);
    }

    /**
     * @inheritdoc
     */
    public function save(string $path, string $content, bool $overwrite = false): void
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        if (!$overwrite && Storage::fileExists($path)) {
            throw new StorageExistsException();
        }
        Storage::put($path, $content);
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newName): void
    {
        $this->validatePath($path);
        $this->validatePath($newName);

        $path = $this->resolvePath($path);
        if (Storage::fileMissing($path)) {
            throw new StorageNotFoundException();
        }

        $newPath = dirname($path).DIRECTORY_SEPARATOR.$newName;
        if ($path === $newPath) {
            return;
        }
        if (Storage::fileExists($newPath)) {
            throw new StorageExistsException();
        }
        Storage::move($path, $newPath);

        $dir = dirname($path).DIRECTORY_SEPARATOR.'.'.basename($path);
        if (Storage::directoryExists($dir)) {
            Storage::move($dir, dirname($path).DIRECTORY_SEPARATOR.'.'.$newName);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $path): void
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        if (Storage::fileMissing($path)) {
            throw new StorageNotFoundException();
        }
        Storage::delete($path);
        $dir = dirname($path).DIRECTORY_SEPARATOR.'.'.basename($path);
        if (Storage::directoryExists($dir)) {
            Storage::deleteDirectory($dir);
        }
    }
}
