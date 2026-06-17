<?php

use Vinkla\Hashids\Facades\Hashids;

if (! function_exists('hashid_encode')) {
    function hashid_encode(int|string $id): string
    {
        return Hashids::encode((int) $id);
    }
}

if (! function_exists('hashid_decode')) {
    function hashid_decode(string $hash): ?int
    {
        $decoded = Hashids::decode($hash);

        return $decoded[0] ?? null;
    }
}
