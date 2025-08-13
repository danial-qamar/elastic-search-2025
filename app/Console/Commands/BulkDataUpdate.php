<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessCsvFile;

class BulkDataUpdate extends Command
{
    protected $signature = 'bulk_data_update:command';
    protected $description = 'Imports CSV data based on subdivision codes';

    public function handle()
    {
        $filepath = storage_path('app/data-import/newPescoTesting');
        Log::info("Import started from: $filepath");

        $subdivisions = DB::table('subdivision')->get();
        $divisionMap = [
            'local' => ['27' => 'PESCO', '26' => 'PESCO'],
            'production' => [
                '11' => 'LESCO', '12' => 'GEPCO', '13' => 'FESCO', '14' => 'IESCO', '15' => 'MEPCO',
                '26' => 'PESCO', '37' => 'HESCO', '38' => 'SEPCO', '48' => 'QESCO', '59' => 'TESCO'
            ]
        ];
        
        $environment = app()->environment();
        $divisionCodes = $divisionMap[$environment] ?? [];
        $totalFiles = 0;

        foreach ($subdivisions as $subdivision) {
            $divisionCode = substr($subdivision->code, 0, 2);
            $company = $divisionCodes[$divisionCode] ?? null;

            if (!$company) {
                continue;
            }

            $fileName = "$company{$subdivision->code}.csv";
            $filePath = "$filepath/$fileName";

            if (!File::exists($filePath)) {
                Log::warning("Missing file: $filePath");
                Log::debug("Looking for: $fileName in $filepath");
                continue;
            }

            Log::info("Dispatching job for: $filePath");
            ProcessCsvFile::dispatch($filePath)->onQueue('csv-import');
            $totalFiles++;
        }

        Log::info("Total files dispatched for processing: $totalFiles");
    }
}

