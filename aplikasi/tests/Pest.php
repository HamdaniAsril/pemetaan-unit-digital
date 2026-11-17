<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

// VACUUM INTO tidak boleh di dalam transaksi, jadi test backup tidak memakai RefreshDatabase.
pest()->extend(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Backup');
