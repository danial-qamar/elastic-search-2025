<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubdivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $subdivisions = [
            ['code' => '27111', 'name' => 'CITY S/DIV HRP'],
            ['code' => '27112', 'name' => 'KHANPUR'],
            ['code' => '27113', 'name' => 'TIP S/DIV HARIPUR'],
            ['code' => '27114', 'name' => 'SN KHAN'],
            ['code' => '27115', 'name' => 'MANG'],
            ['code' => '27121', 'name' => 'ATD CITY 1'],
            ['code' => '27122', 'name' => 'SHIMLA HILL ATD'],
            ['code' => '27123', 'name' => 'HAVALIAN I'],
            ['code' => '27124', 'name' => 'LORA CHOWK'],
            ['code' => '27125', 'name' => 'CITY-II ATD'],
            ['code' => '27126', 'name' => 'HAVALIAN-II'],
            ['code' => '27131', 'name' => 'KHALABAT T/SHIP'],
            ['code' => '27132', 'name' => 'HATTAR S/DIV HRP'],
            ['code' => '27133', 'name' => 'GHAZI'],
            ['code' => '27141', 'name' => 'J-ABAD S/DIV'],
            ['code' => '27142', 'name' => 'NATHIAGALI'],
            ['code' => '27143', 'name' => 'NAWANSHER'],
            ['code' => '27144', 'name' => 'LORA'],
            ['code' => '27145', 'name' => 'DHAMTOOR'],
            ['code' => '27146', 'name' => 'JINAHABAD 2'],
            ['code' => '27211', 'name' => 'URBAN MANSEHRA'],
            ['code' => '27212', 'name' => 'KHAKI'],
            ['code' => '27213', 'name' => 'BALAKOT'],
            ['code' => '27214', 'name' => 'GARHI HABIBULLAH'],
            ['code' => '27215', 'name' => 'GHAZIKOT'],
            ['code' => '27221', 'name' => 'SHINKIARI'],
            ['code' => '27222', 'name' => 'SIRAN VALLEY'],
            ['code' => '27223', 'name' => 'BATTAGRAM'],
            ['code' => '27224', 'name' => 'BAFFA'],
            ['code' => '27231', 'name' => 'RURAL MANSEHRA'],
            ['code' => '27232', 'name' => 'OGHI'],
            ['code' => '27233', 'name' => 'DARBAND'],
        ];

        DB::table('subdivision')->insert($subdivisions);
    }
}
