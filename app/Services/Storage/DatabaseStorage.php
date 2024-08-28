<?php

namespace SevereHeadache\Coffre\Services\Storage;

use Illuminate\Support\Facades\DB;
use SevereHeadache\Coffre\Models\Document;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;
use SevereHeadache\Coffre\Utils\DocumentPathConverter;

/**
 * Database storage operations service.
 */
class DatabaseStorage extends AbstractStorage
{
    /**
     * Translate path to model path.
     *
     * @return string[]
     */
    private function resolvePath(string $path): array
    {
        return explode('/', $path);
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array
    {
        $documents = Document::orderBy('path')->get();

        $result = ['children' => []];
        foreach ($documents as $document) {
            $proc = &$result;
            foreach ($document->path as $pathPart) {
                $proc = &$proc['children'];
                if (!isset($proc[$pathPart])) {
                    $proc[$pathPart] = [];
                }
                $proc = &$proc[$pathPart];
            }
            $proc['name'] = $document->name;
            $proc['path'] = implode('/', $document->path);
        }

        return $result['children'];
    }

    /**
     * @inheritdoc
     */
    public function contents(string $path): string
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        $document = Document::where(
            'path',
            DocumentPathConverter::toString($path),
        )->first();
        if ($document === null) {
            throw new StorageNotFoundException();
        }

        return $document->value;
    }

    /**
     * @inheritdoc
     */
    public function save(string $path, string $content, bool $overwrite = false): void
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        $document = Document::where(
            'path',
            DocumentPathConverter::toString($path),
        )->first();
        if ($document !== null) {
            if (!$overwrite) {
                throw new StorageExistsException();
            }
        } else {
            $document = new Document();
            $document->name = end($path);
            $document->path = $path;
        }
        $document->value = $content;
        $document->save();
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newName): void
    {
        $this->validatePath($path);
        $this->validatePath($newName);

        $path = $this->resolvePath($path);
        $pathRaw = DocumentPathConverter::toString($path);
        $document = Document::where('path', $pathRaw)->exists();
        if (!$document) {
            throw new StorageNotFoundException();
        }

        $newPath = $path;
        $newPath[array_key_last($newPath)] = $newName;
        $newPathRaw = DocumentPathConverter::toString($newPath);
        $exists = Document::where('path', $newPathRaw)->exists();
        if ($exists) {
            throw new StorageExistsException();
        }

        $level = count($path);
        DB::transaction(function() use ($pathRaw, $level, $newName, $newPathRaw) {
            Document::whereRaw('path > \''.$pathRaw.'\'')
                ->update(['path' => DB::raw('
                    subpath(path, 0, '.($level - 1).") ||
                    '$newName'
                    || subpath(path, $level)
                ")]);
            Document::where('path', $pathRaw)
                ->update([
                    'name' => $newName,
                    'path' => $newPathRaw,
                ]);
        });
    }

    /**
     * @inheritdoc
     */
    public function delete(string $path): void
    {
        $this->validatePath($path);

        $path = $this->resolvePath($path);
        $path = DocumentPathConverter::toString($path);
        $exists = Document::where('path', $path)->exists();
        if (!$exists) {
            throw new StorageNotFoundException();
        }

        Document::where('path', '~', $path.'.*')->delete();
    }
}
