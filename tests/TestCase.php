<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Pengaman: RefreshDatabase menghapus semua tabel. Pastikan test
     * tidak pernah jalan di database development/production.
     */
    protected function setUpTraits()
    {
        $db = config('database.connections.' . config('database.default') . '.database');

        if (! str_ends_with((string) $db, '_test') && $db !== ':memory:') {
            throw new \RuntimeException("Test dibatalkan: database '{$db}' bukan database test. Cek phpunit.xml / config cache.");
        }

        return parent::setUpTraits();
    }
}
