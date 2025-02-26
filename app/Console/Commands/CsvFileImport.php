<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CsvFileImport extends Command
{
    protected $signature = 'csv_file_import:command';
    protected $description = 'Imports CSV data based on subdivision codes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filepath = storage_path('app/data-import');
        Log::info($filepath);
        $subdivisions = DB::table('subdivision')->get();
        $divisionMap = [
            'local' => ['11' => 'FESCO', '13' => 'FESCO'],
            'production' => [
                '11' => 'LESCO', '12' => 'GEPCO', '13' => 'FESCO', '14' => 'IESCO', '15' => 'MEPCO',
                '26' => 'PESCO', '37' => 'HESCO', '38' => 'SEPCO', '48' => 'QESCO', '59' => 'TESCO'
            ]
        ];
        
        $environment = app()->environment();
        $divisionCodes = $divisionMap[$environment] ?? [];
        $matchCount = 0;

        foreach ($subdivisions as $subdivision) {
            $divisionCode = substr($subdivision->code, 0, 2);
            $company = $divisionCodes[$divisionCode] ?? null;

            if (!$company) {
                continue;
            }

            $fileName = "$company{$subdivision->code}.csv";
            $filePath = "$filepath/$fileName";

            if (!File::exists($filePath)) {
                continue;
            }

            Log::info("Processing: $filePath");
            $query = "LOAD DATA LOCAL INFILE '" . addslashes($filePath) . "' INTO TABLE consumers
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
                Log::info("Data Imported Successfully for: $fileName");
            } catch (\Exception $e) {
                Log::error('Error importing data for ' . $fileName . ': ' . $e->getMessage());
            }
            
        }
        Log::info("Total Matched Files Processed: $matchCount");
    }
}
