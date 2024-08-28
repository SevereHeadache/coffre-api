<?php

namespace Tests\Unit\Services\Storage;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;
use SevereHeadache\Coffre\Services\Storage\FileStorage;
use Tests\TestCase;

class FileStorageTest extends TestCase
{
    private FileStorage $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->fileStorage = new FileStorage();
    }

    private function expectInvalidPathThrowsException(string $path): void
    {
        if (str_contains($path, '.') || strlen($path) === 0) {
            $this->expectException(InvalidArgumentException::class);
        }
    }

    #[Test]
    public function test_get_all(): void
    {
        foreach ([
            'e1',
            '.e1/e2',
            '.e1/.e2/e3',
            '.e1/e4',
        ] as $path) {
            Storage::put($path, '');
        }

        $this->assertSame([
            'e1' => [
                'name' => 'e1',
                'path' => 'e1',
                'children' => [
                    'e2' => [
                        'name' => 'e2',
                        'path' => 'e1/e2',
                        'children' => [
                            'e3' => [
                                'name' => 'e3',
                                'path' => 'e1/e2/e3',
                            ],
                        ],
                    ],
                    'e4' => [
                        'name' => 'e4',
                        'path' => 'e1/e4',
                    ],
                ],
            ],
        ], $this->fileStorage->getAll());
    }

    public static function provides_contents(): array
    {
        return [
            ['e1', 'e1', 'e1data'],
            ['e1/e2', '.e1/e2', 'e2data'],
            ['e1/e2/e3', '.e1/.e2/e3', 'e3data'],
            ['', 'e4', ''],
            ['.e5', 'e5', ''],
        ];
    }

    #[Test]
    #[DataProvider('provides_contents')]
    public function test_contents(string $path, string $realPath, string $content): void
    {
        Storage::put($realPath, $content);
        $this->expectInvalidPathThrowsException($path);
        $this->assertSame($content, $this->fileStorage->contents($path));
    }

    #[Test]
    public function test_contents_is_not_possible_on_missing_document(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->fileStorage->contents('e1');
    }

    public static function provides_save(): array
    {
        return [
            ['e1', 'e1data', false, 'e1'],
            ['e1/e2', 'e2data', false, '.e1/e2'],
            ['e1/e2/e3', 'e3data', false, '.e1/.e2/e3'],
            ['e1/e2', 'e3alt', true, '.e1/e2'],
            ['e1/e2/.e4', 'e4data', false, ''],
            ['', '', false, ''],
        ];
    }

    #[Test]
    #[DataProvider('provides_save')]
    public function test_save(string $path, string $content, bool $overwrite, string $realPath): void
    {
        $this->expectInvalidPathThrowsException($path);
        $this->fileStorage->save($path, $content, $overwrite);
        $this->assertTrue(Storage::fileExists($realPath));
        $this->assertSame($content, Storage::read($realPath));
    }

    #[Test]
    public function test_save_is_not_possible_on_overwrite(): void
    {
        Storage::put('e1', 'e1data');
        $this->expectException(StorageExistsException::class);
        $this->fileStorage->save('e1', 'e1alt', false);
    }

    public static function provides_rename(): array
    {
        return [
            ['e1/e2/e3', 'e4', ['.e1/.e2/e4']],
            ['e1/e2', 'e4', ['.e1/e4', '.e1/.e4/e3']],
            ['e1', 'e4', ['e4', '.e4/e2', '.e4/.e2/e3']],
            ['e1/.e2', 'e3', []],
            ['', 'e3', []],
            ['e1', '', []],
            ['e1/e2', '.e2', []],
        ];
    }

    #[Test]
    #[DataProvider('provides_rename')]
    public function test_rename(string $path, string $newName, array $updatedPaths): void
    {
        foreach ([
            'e1',
            '.e1/e2',
            '.e1/.e2/e3',
        ] as $realPath) {
            Storage::put($realPath, '');
        }

        $this->expectInvalidPathThrowsException($path);
        $this->expectInvalidPathThrowsException($newName);
        $this->fileStorage->rename($path, $newName);
        foreach ($updatedPaths as $updatedPath) {
            $this->assertTrue(Storage::fileExists($updatedPath));
        }
    }

    #[Test]
    public function test_rename_is_not_possible_on_missing_document(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->fileStorage->rename('e1', 'e2');
    }

    #[Test]
    public function test_rename_on_existing_document_is_not_possible(): void
    {
        Storage::put('e1', '');
        Storage::put('e2', '');
        $this->expectException(StorageExistsException::class);
        $this->fileStorage->rename('e1', 'e2');
    }

    public static function provides_delete(): array
    {
        return [
            ['e1/e2/e3', ['.e1/.e2/e3']],
            ['e1', ['.e1/.e2/e3', '.e1/e2', 'e1']],
            ['', []],
            ['e1/.e2', []],
        ];
    }

    #[Test]
    #[DataProvider('provides_delete')]
    public function test_delete(string $path, array $expectDeleted): void
    {
        foreach ([
            'e1',
            '.e1/e2',
            '.e1/.e2/e3',
        ] as $realPath) {
            Storage::put($realPath, '');
        }

        $this->expectInvalidPathThrowsException($path);
        $this->fileStorage->delete($path);
        foreach ($expectDeleted as $deletedPath) {
            $this->assertTrue(Storage::fileMissing($deletedPath));
        }
    }

    #[Test]
    public function test_delete_is_not_possible_on_missing_document(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->fileStorage->delete('e1');
    }
}
