<?php

namespace App\Policies;

use App\Models\RotationTire;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RotationTirePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {

    }

    public function view(User $user, RotationTire $rotationTire)
    {
    }

    public function create(User $user)
    {
    }

    public function update(User $user, RotationTire $rotationTire)
    {
    }

    public function delete(User $user, RotationTire $rotationTire)
    {
    }

    public function restore(User $user, RotationTire $rotationTire)
    {
    }

    public function forceDelete(User $user, RotationTire $rotationTire)
    {
    }
}
