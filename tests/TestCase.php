<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create an authenticated user and return the bearer token.
     */
    protected function authenticateUser($user = null): string
    {
        $user = $user ?? \App\Domain\User\Models\User::factory()->create();
        return $user->createToken('test-token')->plainTextToken;
    }

    /**
     * Make an authenticated request.
     */
    protected function authenticatedJson(string $method, string $uri, array $data = [], $user = null)
    {
        $token = $this->authenticateUser($user);
        return $this->withHeader('Authorization', "Bearer {$token}")
            ->json($method, $uri, $data);
    }
}
