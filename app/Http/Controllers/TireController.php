<?php

namespace App\Http\Controllers;

use App\Http\Requests\TireRequest;
use App\Models\Tire;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TireController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Tire::class);

        return Tire::all();
    }

    public function store(TireRequest $request)
    {
        $this->authorize('create', Tire::class);

        return Tire::create($request->validated());
    }

    public function show(Tire $tire)
    {
        $this->authorize('view', $tire);

        return $tire;
    }

    public function update(TireRequest $request, Tire $tire)
    {
        $this->authorize('update', $tire);

        $tire->update($request->validated());

        return $tire;
    }

    public function destroy(Tire $tire)
    {
        $this->authorize('delete', $tire);

        $tire->delete();

        return response()->json();
    }
}
