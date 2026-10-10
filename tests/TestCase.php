<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Route uji halaman 500: hanya di environment testing, jadi
        // AdminErrorLogTest bisa memicu error asli lewat HTTP. Dua route
        // beda baris error → beda sidik jari → beda kode error.
        if (app()->environment('testing')) {
            Route::get('/_test-500', fn () => throw new \RuntimeException('x-test'));
            Route::get('/_test-500-json', fn () => throw new \RuntimeException('x-test'));
        }
    }
}
