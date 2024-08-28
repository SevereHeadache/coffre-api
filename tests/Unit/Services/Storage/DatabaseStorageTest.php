<?php

namespace Tests\Unit\Services\Storage;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SevereHeadache\Coffre\Models\Document;
use SevereHeadache\Coffre\Services\Storage\DatabaseStorage;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageExistsException;
use SevereHeadache\Coffre\Services\Storage\Exceptions\StorageNotFoundException;
use Tests\TestCase;

class DatabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseStorage $databaseStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseStorage = new DatabaseStorage();
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
            ['e1'],
            ['e1', 'e2'],
            ['e1', 'e2', 'e3'],
            ['e1', 'e4'],
        ] as $path) {
            Document::factory()->createOne([
                'name' => end($path),
                'path' => $path,
            ]);
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
        ], $this->databaseStorage->getAll());
    }

    public static function provides_contents(): array
    {
        return [
            ['e1', ['e1'], 'e1data'],
            ['e1/e2', ['e1', 'e2'], 'e2data'],
            ['e1/e2/e3', ['e1', 'e2', 'e3'], 'e3data'],
            ['', ['e4'], ''],
            ['.e5', ['e5'], ''],
        ];
    }

    #[Test]
    #[DataProvider('provides_contents')]
    public function test_contents(string $path, array $modelPath, string $content): void
    {
        Document::factory()->createOne([
            'path' => $modelPath,
            'value' => $content,
        ]);
        $this->expectInvalidPathThrowsException($path);
        $this->assertSame($content, $this->databaseStorage->contents($path));
    }

    #[Test]
    public function test_contents_is_not_possible_on_missing_file(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->databaseStorage->contents('e1');
    }

    public static function provides_save(): array
    {
        return [
            ['e1', 'e1data', false, 'e1'],
            ['e1/e2', 'e2data', false, 'e1.e2'],
            ['e1/e2/e3', 'e3data', false, 'e1.e2.e3'],
            ['e1/e2', 'e3alt', true, 'e1.e2'],
            ['e1/e2/.e4', 'e4data', false, ''],
            ['', '', false, ''],
        ];
    }

    #[Test]
    #[DataProvider('provides_save')]
    public function test_save(string $path, string $content, bool $overwrite, string $realPath): void
    {
        $this->expectInvalidPathThrowsException($path);
        $this->databaseStorage->save($path, $content, $overwrite);

        $pathArray = explode('.', $realPath);
        $this->assertDatabaseHas(Document::class, [
            'path' => $realPath,
            'name' => end($pathArray),
            'value' => $content,
        ]);
    }

    #[Test]
    public function test_save_is_not_possible_on_overwrite(): void
    {
        Document::factory()->createOne([
            'name' => 'e1',
            'path' => ['e1'],
            'value' => 'e1data',
        ]);
        $this->expectException(StorageExistsException::class);
        $this->databaseStorage->save('e1', 'e1alt', false);
    }

    public static function provides_rename(): array
    {
        return [
            ['e1/e2/e3', 'e4', ['e1.e2.e4']],
            ['e1/e2', 'e4', ['e1.e4', 'e1.e4.e3']],
            ['e1', 'e4', ['e4', 'e4.e2', 'e4.e2.e3']],
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
            ['e1'],
            ['e1', 'e2'],
            ['e1', 'e2', 'e3'],
        ] as $modelPath) {
            Document::factory()->createOne([
                'name' => end($modelPath),
                'path' => $modelPath,
            ]);
        }

        $this->expectInvalidPathThrowsException($path);
        $this->expectInvalidPathThrowsException($newName);
        $this->databaseStorage->rename($path, $newName);
        foreach ($updatedPaths as $updatedPath) {
            $this->assertDatabaseHas(Document::class, [
                'path' => $updatedPath,
            ]);
        }
    }

    #[Test]
    public function test_rename_is_not_possible_on_missing_document(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->databaseStorage->rename('e1', 'e2');
    }

    #[Test]
    public function test_rename_on_existing_document_is_not_possible(): void
    {
        Document::factory()->count(2)->state(new Sequence(
            ['path' => ['e1']],
            ['path' => ['e2']],
        ))->create();
        $this->expectException(StorageExistsException::class);
        $this->databaseStorage->rename('e1', 'e2');
    }

    public static function provides_delete(): array
    {
        return [
            ['e1/e2/e3', ['e1.e2.e3']],
            ['e1', ['e1.e2.e3', 'e1.e2', 'e1']],
            ['', []],
            ['e1/.e2', []],
        ];
    }

    #[Test]
    #[DataProvider('provides_delete')]
    public function test_delete(string $path, array $expectDeleted): void
    {
        foreach ([
            ['e1'],
            ['e1', 'e2'],
            ['e1', 'e2', 'e3'],
        ] as $modelPath) {
            Document::factory()->createOne([
                'name' => end($modelPath),
                'path' => $modelPath,
            ]);
        }

        $this->expectInvalidPathThrowsException($path);
        $this->databaseStorage->delete($path);
        foreach ($expectDeleted as $deletedPath) {
            $this->assertDatabaseMissing(Document::class, [
                'path' => $deletedPath,
            ]);
        }
    }

    #[Test]
    public function test_delete_is_not_possible_on_missing_document(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->databaseStorage->delete('e1');
    }
}
