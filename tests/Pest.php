<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature-Tests laufen mit dem Laravel-TestCase und frischer Datenbank.
| Unit-Tests bleiben framework-frei (reines PHPUnit-TestCase von Pest).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
