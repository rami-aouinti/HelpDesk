<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CSRF is a browser/session concern. Feature tests focus on the
        // application behavior behind the middleware and should not need
        // to manually manufacture browser tokens for every POST/PATCH.
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withoutVite();
    }
}
