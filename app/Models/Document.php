<?php

namespace SevereHeadache\Coffre\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SevereHeadache\Coffre\Utils\DocumentPathConverter;

/**
 * Document.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $value
 * @property string[]    $path
 */
class Document extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_resource($value)
                ? stream_get_contents($value)
                : $value,
        );
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn(string $path) => DocumentPathConverter::toArray($path),
            set: fn(array $path) => DocumentPathConverter::toString($path),
        );
    }
}
