<?php

namespace SevereHeadache\Coffre\Utils;

/**
 * @see SevereHeadache\Coffre\Models\Document
 */
class DocumentPathConverter
{
    /**
     * @return string[]
     */
    public static function toArray(string $path): array
    {
        return explode('.', $path);
    }

    /**
     * @param string[] $path
     */
    public static function toString(array $path): string
    {
        return implode('.', $path);
    }
}
