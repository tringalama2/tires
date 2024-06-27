<?php

namespace App\Http\Controllers;

use App\Http\Requests\RotationRequest;
use App\Models\Rotation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RotationController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Rotation::class);

        return Rotation::all();
    }

    public function store(RotationRequest $request)
    {
        $this->authorize('create', Rotation::class);

        return Rotation::create($request->validated());
    }

    public function show(Rotation $rotations)
    {
        $this->authorize('view', $rotations);

        return $rotations;
    }

    public function update(RotationRequest $request, Rotation $rotations)
    {
        $this->authorize('update', $rotations);

        $rotations->update($request->validated());

        return $rotations;
    }

    public function destroy(Rotation $rotations)
    {
        $this->authorize('delete', $rotations);

        $rotations->delete();

        return response()->json();
    }
}
