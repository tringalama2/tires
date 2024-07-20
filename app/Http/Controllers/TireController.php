<?php

namespace App\Http\Controllers;

use App\Http\Requests\TireRequest;
use App\Models\Tire;
use App\Models\Vehicle;

class TireController extends Controller
{
    public function index(Vehicle $vehicle)
    {
        return view('tires.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Vehicle $vehicle)
    {
        return view('tires.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TireRequest $request, Vehicle $vehicle)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle, Tire $tire)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle, Tire $tire)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TireRequest $request, Vehicle $vehicle, Tire $tire)
    {
        //
    }
}
