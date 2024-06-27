<?php

namespace App\Policies;

use App\Models\Tire;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TirePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {

    }

    public function view(User $user, Tire $tire)
    {
    }

    public function create(User $user)
    {
    }

    public function update(User $user, Tire $tire)
    {
    }

    public function delete(User $user, Tire $tire)
    {
    }

    public function restore(User $user, Tire $tire)
    {
    }

    public function forceDelete(User $user, Tire $tire)
    {
    }
}
