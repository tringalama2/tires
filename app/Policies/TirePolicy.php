<?php

namespace App\Policies;

use App\Models\Tire;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TirePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tire $tire): Response
    {
        return $user->id === $tire->vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tire $tire): Response
    {
        return $user->id === $tire->vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Tire $tire): Response
    {
        return $user->id === $tire->vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function restore(User $user, Tire $tire): Response
    {
        return $user->id === $tire->vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function forceDelete(User $user, Tire $tire): Response
    {
        return $user->id === $tire->vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
