<?php

namespace App\Concerns;

use Vinkla\Hashids\Facades\Hashids;

trait HasHashid
{
    public function getRouteKey(): string
    {
        return Hashids::encode($this->getKey());
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $decoded = Hashids::decode($value);

        if (empty($decoded)) {
            return null;
        }

        return $this->where('id', $decoded[0])->first();
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?static
    {
        return $this->resolveRouteBinding($value, $field);
    }
}
