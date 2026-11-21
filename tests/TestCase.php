<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Browser CSRF checks are covered by the framework. Feature tests
        // focus on the application behavior behind this middleware.
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->withoutVite();
    }
}
