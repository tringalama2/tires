<?php

namespace App\Policies;

use App\Models\Rotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {

    }

    public function view(User $user, Rotation $rotations)
    {
    }

    public function create(User $user)
    {
    }

    public function update(User $user, Rotation $rotations)
    {
    }

    public function delete(User $user, Rotation $rotations)
    {
    }

    public function restore(User $user, Rotation $rotations)
    {
    }

    public function forceDelete(User $user, Rotation $rotations)
    {
    }
}
