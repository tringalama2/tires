<?php

namespace App\Actions;

use App\Enums\TireStatus;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class PredictCurrentOdometer
{
    const ROTATIONS_TO_USE_FOR_PREDICTION = 4;

    public function __invoke(Vehicle $vehicle): ?int
    {
        $query = <<<'QRY'
                    SELECT
                        @a_count := avg(starting_odometer)                                 as mean_count,
                           @a_days := avg(days)                                             as mean_days,
                           @covariance := (sum(days * starting_odometer) - sum(days) * sum(starting_odometer) / count(days)) /
                                          count(days)                                       as covariance,
                           @stddev_count := stddev(starting_odometer)                       as stddev_count,
                           @stddev_day := stddev(days)                                      as stddev_week,
                           @r := @covariance / (@stddev_count * @stddev_day)                  as r,
                           @slope := @r * @stddev_count / @stddev_day                         as slope,
                           @y_int := @a_count - (@slope * @a_days)                            as y_int,
                           @this_day_no := TO_DAYS(curdate())                                 as this_day_no,
                           @predicted := round(greatest(1, @y_int + (@slope * @this_day_no))) as predicted

                    FROM (select distinct starting_odometer, rotated_on, TO_DAYS(rotated_on) as days
                          from rotations
                          where exists (select *
                                        from tires
                                        where rotations.tire_id = tires.id
                                          and vehicle_id = :vehicle_id
                                          and status = :status)
                          order by starting_odometer desc
                          limit :limit) as rotations
                    QRY;
        $regression = DB::select($query, [
            'vehicle_id' => $vehicle->id,
            'status' => TireStatus::Installed->value,
            'limit' => self::ROTATIONS_TO_USE_FOR_PREDICTION,
        ]);

        return $regression[0]?->predicted;

        //        $rotations = Rotation::query()
        //            ->select(['starting_odometer', 'rotated_on'])
        //            ->selectRaw('TO_DAYS(rotated_on) as days')
        //            ->distinct()
        //            ->whereHas('tire', function (Builder $query) use ($vehicle) {
        //                $query->where('vehicle_id', '=', $vehicle->id)
        //                    ->installed();
        //            })
        //            ->orderByDesc('starting_odometer')
        //            ->limit(self::RotationsToIncludeInRegression)
        //            ->ddRawSql();
    }
}
