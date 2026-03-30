<?php

declare(strict_types=1);

namespace App\Support\Media;

final class SecureImagePath
{
    /**
     * @param  array<int, non-empty-string>  $allowedPrefixes
     */
    public static function assertRelativePathMatches(string $relativePath, array $allowedPrefixes): void
    {
        if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            throw new \InvalidArgumentException('Invalid storage path.');
        }

        $normalized = ltrim($relativePath, '/');

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return;
            }
        }

        throw new \InvalidArgumentException('Storage path is outside allowed directories.');
    }
}
