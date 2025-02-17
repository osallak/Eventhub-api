<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PhpFileHelper
{
    public static function getImportLines(string $filePath): Collection
    {
        return collect(file($filePath))->takeUntil(
            function ($line) {
                return Str::contains($line, '{');
            }
        )->filter(
            function ($line) {
                return Str::contains($line, 'use');
            }
        );
    }

    public static function getNameSpace(string $filePath): string
    {
        return Str::of(
            collect(file($filePath))->first(
                function ($line) {
                    return Str::contains($line, 'namespace');
                }
            )
        )->between(' ', ';');
    }
}
