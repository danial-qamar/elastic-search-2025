<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LoadCsvData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-csv-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will load data directly into consumers table without matching any subdivision';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $startTime = microtime(true);
        $filepath = storage_path('app/data-import');

        if (!File::exists($filepath)) {
            $this->error("Data import folder not found: $filepath");
            return;
        }

        DB::table('consumers')->truncate();
        DB::table('subdivision')->truncate();

        $files = File::files($filepath);
        $records = [];
        $matchCount = 0;
        $totalRecordsImported = 0;

        foreach ($files as $file) {
            $fileStart = microtime(true);
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (preg_match('/^([A-Z]+)\s*(\d{5})$/i', $filename, $matches)) {
                $discoName = strtoupper($matches[1]);
                $code = $matches[2];

                $records[] = [
                    'name' => $discoName,
                    'code' => $code,
                ];

                $filePath = $file->getPathname();

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
                    SET reference_no = TRIM(REGEXP_REPLACE(@reference_no, '[^A-Z0-9]', '')),
                        occupant_nicno = TRIM(REGEXP_REPLACE(@occupant_nicno, '[^A-Z0-9]', '')),
                        contactno = TRIM(REGEXP_REPLACE(@contactno, '[^A-Z0-9]', ''))";

                try {
                    DB::unprepared($query);
                    $matchCount++;
                    
                    $countForFile = DB::table('consumers')
                    ->whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$code])
                    ->count();
                    $totalRecordsImported += $countForFile;
                    $fileTime = round(microtime(true) - $fileStart, 2);

                    $this->info("✅ {$file->getFilename()} processed: {$countForFile} records in {$fileTime}s");
                } catch (\Exception $e) {
                    Log::error('Error importing data for ' . $file->getFilename() . ': ' . $e->getMessage());
                }
            } else {
                Log::warning("Filename does not match expected format: {$filename}");
            }
        }

        if (!empty($records)) {
            DB::table('subdivision')->insert($records);
            $this->info(count($records) . " subdivisions inserted successfully.");
        }

        $this->info("Total CSV files processed into consumers: $matchCount");
        $totalTime = round(microtime(true) - $startTime, 2);
        $this->line(str_repeat('-', 50));
        $this->info("📊 Import Summary:");
        $this->info("   Total files processed: $matchCount");
        $this->info("   Total records imported: $totalRecordsImported");
        $this->info("   Average per file: " . round($totalRecordsImported / max(1, $matchCount), 2));
        $seconds = $totalTime;

        if ($seconds < 60) {
            $formattedTime = "{$seconds}s";
        } elseif ($seconds < 3600) {
            $formattedTime = round($seconds / 60, 2) . "m";
        } elseif ($seconds < 86400) {
            $formattedTime = round($seconds / 3600, 2) . "h";
        } else {
            $formattedTime = round($seconds / 86400, 2) . "d";
        }

        $this->info("   Total time: {$formattedTime}");
    }
}
