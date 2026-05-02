<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait ChecksOwnership
{
    /**
     * Abort with 403 if the given closure returns false.
     * Usage: if ($response = $this->assertOwnership($resource, fn() => $user->id === $resource->owner_id)) return $response;
     */
    protected function assertOwnership(Model $resource, \Closure $check): ?JsonResponse
    {
        if (! $check($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return null;
    }

    /**
     * Abort with 403 if $user is not an instance of $class.
     * Usage: if ($response = $this->guardIs($user, Company::class)) return $response;
     */
    protected function guardIs(mixed $user, string $class): ?JsonResponse
    {
        if (! ($user instanceof $class)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return null;
    }
}
