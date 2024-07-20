<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class VehiclePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vehicle $vehicle): Response
    {
        return $user->id === $vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response
    {
        return count($user->vehicles) < Vehicle::MAX_VEHICLES_PER_USER
            ? Response::allow()
            : Response::denyWithStatus(403, 'You are only allowed to create '.Vehicle::MAX_VEHICLES_PER_USER.' vehicles.');
    }

    public function update(User $user, Vehicle $vehicle): Response
    {
        return $user->id === $vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Vehicle $vehicle): Response
    {
        return $user->id === $vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function restore(User $user, Vehicle $vehicle): Response
    {
        return $user->id === $vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function forceDelete(User $user, Vehicle $vehicle): Response
    {
        return $user->id === $vehicle->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
