<?php


use App\Enums\TireStatus;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;

it('benchmark', function () {


    Benchmark::dd([
        'User Count: '.User::count() => fn() => User::count(), // 0.5 ms
//        'User with rotations' => fn() => User::withWhereHas('vehicles', function ($query) {
//            $query->withWhereHas('tires', function ($query) {
//                $query->withWhereHas('rotations', function ($query) {
//                    $query->where('odometer', '>=', 0);
//                });
//            });
//        })->where('id', '=', 1)->get(), // 20.0 ms
//        'tire history window function lag ending tread' => fn() => Rotation::select([
//            'rotated_on', 'starting_odometer',
//            'tin', 'label', 'starting_tread', 'position',
//            DB::raw('lag(starting_tread) over (partition by tires.id order by starting_odometer desc) as ending_tread'),
//            DB::raw('lag(starting_odometer) over (partition by tires.id order by starting_odometer desc) as ending_odometer'),
//
//        ])->join('tires', 'rotations.tire_id', '=', 'tires.id')
//            ->where('tires.status', TireStatus::Installed)
//            ->where('tires.vehicle_id', 1)
//            ->orderBy('odometer', 'desc')
//            ->get(),
    ]);
});
