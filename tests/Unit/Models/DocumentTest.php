<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SevereHeadache\Coffre\Models\Document;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    #[Test]
    public function test_value_accessor(): void
    {
        $data = fake()->text();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $data);
        rewind($stream);

        $document = new Document();
        $document->setRawAttributes(['value' => $stream]);
        $this->assertSame($data, $document->value);

        fclose($stream);
    }

    public static function provides_path(): array
    {
        $data = [
            [$word = fake()->word(), [$word]],
        ];
        for ($i = 0; $i < 5; ++$i) {
            $parts = [];
            for ($n = 0; $n < rand(2, 20); ++$n) {
                $parts[] = fake()->word();
            }
            $data[] = [
                implode('.', $parts),
                $parts,
            ];
        }

        return $data;
    }

    #[Test]
    #[DataProvider('provides_path')]
    public function test_path_accessor(? string $raw, array $path): void
    {
        $document = new Document();
        $document->setRawAttributes(['path' => $raw]);
        $this->assertSame($path, $document->path);
    }

    #[Test]
    #[DataProvider('provides_path')]
    public function test_path_mutator(string $raw, array $path): void
    {
        $document = new Document();
        $document->path = $path;
        $attributes = $document->getAttributes();
        $this->assertSame($raw, $attributes['path']);
    }
}
