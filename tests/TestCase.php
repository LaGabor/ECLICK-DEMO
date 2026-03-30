<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    private const MINI_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->environment('testing')) {
            $root = sys_get_temp_dir().'/eclick-test-private-'.uniqid('', true);
            mkdir($root, 0777, true);
            config(['filesystems.disks.local.root' => $root]);
        }
    }

    protected function seedTinyPngToPrivateDisk(string $relativePath): void
    {
        $binary = base64_decode(self::MINI_PNG_BASE64, true);

        if ($binary === false) {
            throw new \RuntimeException('Invalid test image payload.');
        }

        $disk = Storage::disk((string) config('image_upload.disk'));
        $dir = dirname($relativePath);

        if ($dir !== '.' && $dir !== '') {
            $disk->makeDirectory($dir);
        }

        $disk->put($relativePath, $binary);
    }
}
