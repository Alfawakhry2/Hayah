<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $egyptId = DB::table('countries')->where('name', 'Egypt')->value('id');
        $saudiId = DB::table('countries')->where('name', 'Saudi Arabia')->value('id');
        $governorates = [
            ['country_id' => $egyptId, 'governorate_name' => 'Cairo'],
            ['country_id' => $egyptId, 'governorate_name' => 'Giza'],
            ['country_id' => $egyptId, 'governorate_name' => 'Alexandria'],
            ['country_id' => $egyptId, 'governorate_name' => 'Dakahlia'],
            ['country_id' => $egyptId, 'governorate_name' => 'Red Sea'],
            ['country_id' => $egyptId, 'governorate_name' => 'Beheira'],
            ['country_id' => $egyptId, 'governorate_name' => 'Fayoum'],
            ['country_id' => $egyptId, 'governorate_name' => 'Gharbiya'],
            ['country_id' => $egyptId, 'governorate_name' => 'Ismailia'],
            ['country_id' => $egyptId, 'governorate_name' => 'Menofia'],
            ['country_id' => $egyptId, 'governorate_name' => 'Minya'],
            ['country_id' => $egyptId, 'governorate_name' => 'Qaliubiya'],
            ['country_id' => $egyptId, 'governorate_name' => 'New Valley'],
            ['country_id' => $egyptId, 'governorate_name' => 'Suez'],
            ['country_id' => $egyptId, 'governorate_name' => 'Aswan'],
            ['country_id' => $egyptId, 'governorate_name' => 'Assiut'],
            ['country_id' => $egyptId, 'governorate_name' => 'Beni Suef'],
            ['country_id' => $egyptId, 'governorate_name' => 'Port Said'],
            ['country_id' => $egyptId, 'governorate_name' => 'Damietta'],
            ['country_id' => $egyptId, 'governorate_name' => 'Sharkia'],
            ['country_id' => $egyptId, 'governorate_name' => 'South Sinai'],
            ['country_id' => $egyptId, 'governorate_name' => 'Kafr Al sheikh'],
            ['country_id' => $egyptId, 'governorate_name' => 'Matrouh'],
            ['country_id' => $egyptId, 'governorate_name' => 'Luxor'],
            ['country_id' => $egyptId, 'governorate_name' => 'Qena'],
            ['country_id' => $egyptId, 'governorate_name' => 'North Sinai'],
            ['country_id' => $egyptId, 'governorate_name' => 'Sohag'],
            // Saudi Arabian Governorates - using English names
            ['country_id' => $saudiId, 'governorate_name' => 'Riyadh'],
            ['country_id' => $saudiId, 'governorate_name' => 'Makkah'],
            ['country_id' => $saudiId, 'governorate_name' => 'Madinah'],
            ['country_id' => $saudiId, 'governorate_name' => 'Qassim'],
            ['country_id' => $saudiId, 'governorate_name' => 'Eastern Province'],
            ['country_id' => $saudiId, 'governorate_name' => 'Asir'],
            ['country_id' => $saudiId, 'governorate_name' => 'Tabuk'],
            ['country_id' => $saudiId, 'governorate_name' => 'Hail'],
            ['country_id' => $saudiId, 'governorate_name' => 'Northern Borders'],
            ['country_id' => $saudiId, 'governorate_name' => 'Jazan'],
            ['country_id' => $saudiId, 'governorate_name' => 'Najran'],
            ['country_id' => $saudiId, 'governorate_name' => 'Al Bahah'],
            ['country_id' => $saudiId, 'governorate_name' => 'Al Jawf'],
        ];

        DB::table('governorates')->insert($governorates);
    }
}
