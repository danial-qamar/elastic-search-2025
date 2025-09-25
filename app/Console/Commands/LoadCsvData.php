<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Elasticsearch\ClientBuilder;
use App\Models\Consumer;

class LoadCsvData extends Command
{
    protected $signature = 'app:load-and-index-csv {bill_month?} {--truncate}';

    protected $description = 'Loads CSV files into consumers table and indexes them into Elasticsearch subdivision-by-subdivision.';

    public function handle()
    {
        $startTime = microtime(true);
        $filepath = storage_path('app/data-import');

        if (!File::exists($filepath)) {
            $this->error("Data import folder not found: $filepath");
            return;
        }
        $billMonth = $this->argument('bill_month');

        if (!$billMonth) {
            $this->error("❌ bill_month argument is required");
            return;
        }

        if ($this->option('truncate')) { 
            DB::table('consumers')->truncate(); 
            DB::table('subdivision')->truncate(); 
            if ($billMonth) {
                $logIds = DB::table('import_logs')->where('bill_month', $billMonth)->pluck('id');
                DB::table('import_log_subdivisions')->whereIn('import_log_id', $logIds)->delete();
                DB::table('import_logs')->whereIn('id', $logIds)->delete();
                $this->info("🗑️ import_logs and import_log_subdivisions cleared for bill_month {$billMonth}.");
            }
            $this->info("✅ consumers and subdivision tables truncated."); 
        }

        $files = File::files($filepath);

        $importLog = DB::table('import_logs')->updateOrInsert(
            ['bill_month' => $billMonth],
            [
                'consumers_count'   => 0,
                'subdivisions_count'=> 0,
                'indexed_count'     => 0,
                'updated_at'        => now(),
                'created_at'        => now(),
            ]
        );

        // fetch fresh instance (with id)
        $importLogId = DB::table('import_logs')->where('bill_month', $billMonth)->value('id');

        $records = [];
        $totalIndexed = 0;
        $totalImported = 0;

        $client = ClientBuilder::create()->build();

        foreach ($files as $file) {
            $fileStart = microtime(true);
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            // Expect filenames like "XYZ 12345"
            if (!preg_match('/^([A-Z]+)\s*(\d{5})$/i', $filename, $matches)) {
                Log::warning("Filename does not match expected format: {$filename}");
                continue;
            }

            $discoName = strtoupper($matches[1]);
            $subCode   = $matches[2];

            $records[] = [
                'name' => $discoName,
                'code' => $subCode,
            ];

            $filePath = $file->getPathname();

            $lastIdBefore = DB::table('consumers')->max('id') ?? 0;
            $env = env('APP_ENV');
            $replaceFn = $env === 'prod' ? 'REPLACE' : 'REGEXP_REPLACE';
            // Load CSV into DB
            $query = "LOAD DATA LOCAL INFILE '" . addslashes($filePath) . "' INTO TABLE consumers
                CHARACTER SET latin1
                FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'
                (@reference_no, bill_month, name, fname, address_1, address_2, corporation_name, connection_date, 
                season_dode, season_age, fata_pata_code, it_exempt_code, extra_tax_exempt_code, meter_rent, service_rent, 
                meter_phase, feeder_code, feeder_name, transformer_code, tranformer_address, wapda_employee_bps_code, 
                wapda_employee_name, wapda_department_code, wapda_employee_epf_no, wapda_employee_balance_units, 
                contract_expire_date, appliation_date, security_date, security_amount, nicno, emailaddr, @contactno, 
                no_of_ac, no_of_tv, ntn_no, strn_no, no_of_booster, no_of_poles, current_status, defalter_level, defalter_age, 
                disconnection_issue_no, disconnection_issue_date, disconnection_expiry_date, disconection_age, same_age, 
                kwh_meter_defective_age, total_deffered_amount, total_installemnt, remaining_installment, last_disconnection_date, 
                last_reconnection_date, last_defective_date, last_replacement_date, defective_times, replacement_times, 
                defective_remaning_times, agriculture_motor_code, tv_exempt_code, uniqkey, old_reference_no, old_reference_change_date, 
                gps_longitude, gps_latitude, sub_batch, tariff, sanction_load, connected_load, rural_uraban_code, 
                standard_classification_code, total_kwh_meter, govt_department_code, electricity_duty_code, @occupant_nicno)
                SET reference_no = TRIM({$replaceFn}(@reference_no, '[^A-Z0-9]', '')),
                    subdivision_code = SUBSTRING(TRIM({$replaceFn}(@reference_no, '[^A-Z0-9]', '')), 3, 5),
                    occupant_nicno = TRIM({$replaceFn}(@occupant_nicno, '[^A-Z0-9]', '')),
                    contactno = TRIM({$replaceFn}(@contactno, '[^A-Z0-9]', ''))";


            try {
                DB::unprepared($query);

                // Get last inserted IDs
                $lastIdAfter = DB::table('consumers')->max('id') ?? $lastIdBefore;
                $newConsumers = Consumer::whereBetween('id', [$lastIdBefore + 1, $lastIdAfter])->get();
                $countForFile = $newConsumers->count();
                $totalImported += $countForFile;

                DB::table('import_log_subdivisions')->updateOrInsert(
                    [
                        'import_log_id'    => $importLogId,
                        'subdivision_code' => $subCode,
                    ],
                    [
                        'consumers_count' => DB::raw("consumers_count + {$countForFile}"),
                        'indexed_count'   => DB::raw("indexed_count + {$countForFile}"),
                        'updated_at'      => now(),
                    ]
                );
                $this->info("✅ {$file->getFilename()} loaded: {$countForFile} records");

                // Delete old index for this subdivision
                try {
                    $client->deleteByQuery([
                        'index' => 'consumers',
                        'body'  => [
                            'query' => [
                                'term' => ['subdivision' => $subCode]
                            ]
                        ]
                    ]);
                    $this->info("Old index deleted for subdivision: $subCode");
                } catch (\Exception $e) {
                    $this->warn("No previous index found for subdivision: $subCode");
                }

                // Index only newly loaded consumers in DB-friendly chunks
                $batchSize = 5000;
                Consumer::whereBetween('id', [$lastIdBefore + 1, $lastIdAfter])
                    ->chunkById($batchSize, function ($batch) use ($client, $subCode, &$totalIndexed) {
                        $bulkParams = ['body' => []];
                        foreach ($batch as $consumer) {
                            $data = $consumer->toArray();
                            $data['subdivision'] = $subCode;

                            $bulkParams['body'][] = [
                                'index' => [
                                    '_index' => 'consumers',
                                    '_id'    => $consumer->id,
                                ],
                            ];
                            $bulkParams['body'][] = $data;
                        }
                        $client->bulk($bulkParams);
                        $totalIndexed += $batch->count();
                    });


                $fileTime = round(microtime(true) - $fileStart, 2);
                $this->info("📦 Subdivision $subCode indexed: {$countForFile} records in {$fileTime}s");
                $this->line(str_repeat('-', 60));

            } catch (\Exception $e) {
                Log::error('Error importing/indexing data for ' . $file->getFilename() . ': ' . $e->getMessage());
            }
        }

        if (!empty($records)) {
            DB::table('subdivision')->insert($records);
            $this->info(count($records) . " subdivisions inserted successfully.");
        }

        
        $totalTime = round(microtime(true) - $startTime, 2);
        $importLog = DB::table('import_logs')->where('id', $importLogId)->first();
        $durationToSave = $totalTime;
        if (!$this->option('truncate') && $importLog) {
            $durationToSave = $importLog->duration + $totalTime;
        }
        DB::table('import_logs')
        ->where('id', $importLogId)
        ->update([
            'consumers_count'   => DB::table('import_log_subdivisions')
                                        ->where('import_log_id', $importLogId)
                                        ->sum('consumers_count'),
            'subdivisions_count'=> DB::table('import_log_subdivisions')
                                        ->where('import_log_id', $importLogId)
                                        ->count(),
            'indexed_count'     => DB::table('import_log_subdivisions')
                                        ->where('import_log_id', $importLogId)
                                        ->sum('indexed_count'),
            'duration'          => $durationToSave,
            'updated_at'        => now(),
        ]);
        $this->info("📊 Import + Index Summary:");
        $this->info("   Total records imported: $totalImported");
        $this->info("   Total records indexed: $totalIndexed");
        $this->info("   Total time: {$totalTime}s");
    }
}
