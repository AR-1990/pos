<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MeasurementUnit;

class MeasurementUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'Kilogram (kg)',
            'Gram (g)',
            'Milligram (mg)',
            'Pound (lb)',
            'Ounce (oz)',
            'Liter (l)',
            'Milliliter (ml)',
            'Meter (m)',
            'Centimeter (cm)',
            'Inch (in)',
            'Foot (ft)',
            'Yard (yd)',
            'Piece (pcs)',
            'Dozen (dz)',
            'Box (box)',
            'Packet (pkt)',
            'Bag (bag)',
            'Bundle (bdl)',
            'Roll (roll)',
            'Set (set)',
            'Pair (pair)',
            'Carton (ctn)',
            'Square Meter (sq m)',
            'Square Foot (sq ft)',
            'Cubic Meter (cu m)',
        ];

        foreach ($units as $unit) {
            MeasurementUnit::firstOrCreate(
                ['name' => $unit],
                ['status' => 'Active']
            );
        }
    }
}
