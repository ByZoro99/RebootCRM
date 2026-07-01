<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Los tests estilo Pest (funciones it()/test()/expect()) de las carpetas
| Feature y Unit usan el TestCase de la app y RefreshDatabase, de modo que
| cada test corre contra una BD SQLite en memoria migrada desde cero.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('Unit');
