<?php

namespace SevereHeadache\Coffre\Http\Controllers;

use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;
use SevereHeadache\Coffre\Services\Storage\StorageInterface;

class StorageController extends Controller
{
    public function index(StorageInterface $storageService)
    {
        $tree = $storageService->getAll();

        return response()->json($tree);
    }

    public function contents(string $document, StorageInterface $storageService)
    {
        try {
            $contents = $storageService->contents($document);
        } catch (StorageNotFoundException) {
            return abort(404, 'Document not found');
        }

        return response()->json($contents);
    }

    public function save(string $document, StorageInterface $storageService)
    {
        $validated = request()->validate([
            'content' => 'string',
            'overwrite' => 'boolean',
        ]);
        try {
            $storageService->save(
                $document,
                $validated['content'] ?? '',
                $validated['overwrite'] ?? false,
            );
        } catch (StorageExistsException) {
            return abort(400, 'Document already exists');
        }

        return response()->json();
    }

    public function rename(string $document, StorageInterface $storageService)
    {
        $validated = request()->validate([
            'name' => 'required|string',
        ]);

        try {
            $storageService->rename($document, $validated['name']);
        } catch (StorageNotFoundException) {
            return abort(404, 'Document not found');
        } catch (StorageExistsException) {
            return abort(400, 'Document already exists');
        }

        return response()->json();
    }

    public function delete(string $document, StorageInterface $storageService)
    {
        try {
            $storageService->delete($document);
        } catch (StorageNotFoundException) {
            return abort(404, 'Document not found');
        }

        return response()->json();
    }
}
