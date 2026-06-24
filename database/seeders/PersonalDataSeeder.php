<?php

namespace Database\Seeders;

use App\Enums\TireStatus;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Database\Seeder;

class PersonalDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Steve',
            'last_name' => 'T',
            'email' => 'steve@tires.test',
        ]);

        $vehicle = $user->vehicles()->create([
            'year' => 2017,
            'make' => 'Toyota',
            'model' => '4Runner',
            'nickname' => '4Runner',
            'tire_count' => 5,
            'starting_odometer' => 104400,
        ]);

        $data = json_decode(
            file_get_contents(database_path('../docs/seed-data.json')),
            associative: true
        );

        $tires = collect();
        foreach ($data['tires'] as $tireDef) {
            $tire = Tire::create([
                'vehicle_id' => $vehicle->id,
                'label' => $tireDef['label'],
                'brand' => $tireDef['brand'] ?? null,
                'model' => $tireDef['model'] ?? null,
                'status' => TireStatus::Active,
            ]);
            $tires[$tireDef['label']] = $tire;
        }

        // Setup rotation: establish starting positions from the first real rotation's from_positions.
        // Odometer is one unit below first rotation so real rotations always rank higher in odometer-desc order.
        $firstRotation = $data['rotations'][0];
        $setupRotation = Rotation::create([
            'vehicle_id' => $vehicle->id,
            'rotated_on' => $firstRotation['rotated_at'],
            'odometer' => $vehicle->starting_odometer - 1,
            'is_setup' => true,
        ]);
        foreach ($firstRotation['placements'] as $p) {
            $setupRotation->placements()->create([
                'tire_id' => $tires[$p['tire']]->id,
                'from_position' => null,
                'to_position' => $p['from'],
                'tread_center' => $p['center'],
            ]);
        }

        foreach ($data['rotations'] as $rotationDef) {
            $rotation = Rotation::create([
                'vehicle_id' => $vehicle->id,
                'rotated_on' => $rotationDef['rotated_at'],
                'odometer' => $rotationDef['odometer'],
                'note' => $rotationDef['note'] ?? null,
                'is_setup' => false,
            ]);

            foreach ($rotationDef['placements'] as $p) {
                $rotation->placements()->create([
                    'tire_id' => $tires[$p['tire']]->id,
                    'from_position' => $p['from'],
                    'to_position' => $p['to'],
                    'tread_center' => $p['center'],
                    'tread_inner' => $p['inner'] ?? null,
                    'tread_outer' => $p['outer'] ?? null,
                    'note' => $p['note'] ?? null,
                ]);
            }
        }
    }
}
