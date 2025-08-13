<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Elasticsearch\ClientBuilder;

class ProcessCsvFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $startTime = microtime(true);

        if (!file_exists($this->filePath)) {
            Log::error("File not found: {$this->filePath}");
            return;
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            Log::error("Failed to open file: {$this->filePath}");
            return;
        }

        $columns = [
            'reference_no', 'bill_month', 'name', 'fname', 'address_1', 'address_2', 'corporation_name', 'connection_date',
            'season_dode', 'season_age', 'fata_pata_code', 'it_exempt_code', 'extra_tax_exempt_code', 'meter_rent', 'service_rent',
            'meter_phase', 'feeder_code', 'feeder_name', 'transformer_code', 'tranformer_address', 'wapda_employee_bps_code',
            'wapda_employee_name', 'wapda_department_code', 'wapda_employee_epf_no', 'wapda_employee_balance_units',
            'contract_expire_date', 'appliation_date', 'security_date', 'security_amount', 'nicno', 'emailaddr', 'contactno',
            'no_of_ac', 'no_of_tv', 'ntn_no', 'strn_no', 'no_of_booster', 'no_of_poles', 'current_status', 'defalter_level',
            'defalter_age', 'disconnection_issue_no', 'disconnection_issue_date', 'disconnection_expiry_date', 'disconection_age',
            'same_age', 'kwh_meter_defective_age', 'total_deffered_amount', 'total_installemnt', 'remaining_installment',
            'last_disconnection_date', 'last_reconnection_date', 'last_defective_date', 'last_replacement_date', 'defective_times',
            'replacement_times', 'defective_remaning_times', 'agriculture_motor_code', 'tv_exempt_code', 'uniqkey', 'old_reference_no',
            'old_reference_change_date', 'gps_longitude', 'gps_latitude', 'sub_batch', 'tariff', 'sanction_load', 'connected_load',
            'rural_uraban_code', 'standard_classification_code', 'total_kwh_meter', 'govt_department_code', 'electricity_duty_code', 'occupant_nicno'
        ];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) > count($columns)) {
                $row = array_slice($row, 0, count($columns));
            }

            $data = array_combine($columns, $row);

            // Clean inputs
            $data['reference_no'] = trim(preg_replace('/[^A-Z0-9]/', '', $data['reference_no']));
            $data['occupant_nicno'] = trim(preg_replace('/[^A-Z0-9]/', '', $data['occupant_nicno']));
            $data['contactno'] = trim(preg_replace('/[^A-Z0-9]/', '', $data['contactno']));

            // Get existing record with ID
            $existing = DB::table('consumers')
                ->where('reference_no', $data['reference_no'])
                ->first();

            if ($existing) {
                $existingArray = (array) $existing;

                // Remove fields that are not in $data before comparison
                unset($existingArray['id'], $existingArray['created_at'], $existingArray['updated_at']);

                $differences = array_diff_assoc($existingArray, $data);
                Log::info('EXISTING:', $existingArray);
                Log::info('NEW DATA:', $data);
                Log::info("Differences found:\n" . json_encode($differences, JSON_PRETTY_PRINT));
                if (!empty($differences)) {
                    // Update DB
                    DB::table('consumers')
                        ->where('id', $existing->id)
                        ->update($data);

                    // Fetch updated record and update ES
                    $updatedConsumer = DB::table('consumers')->find($existing->id);
                    $this->updateElasticSearch($existing->id, (array) $updatedConsumer);

                    Log::info("Updated record: {$data['reference_no']}");
                } else {
                    Log::info("No changes for: {$data['reference_no']}");
                }
            } else {
                // Insert new record
                $id = DB::table('consumers')->insertGetId($data);
                $newConsumer = DB::table('consumers')->find($id);

                $this->insertElasticSearch($id, (array) $newConsumer);
                Log::info("Inserted new record: {$data['reference_no']}");
            }
        }

        fclose($handle);

        $executionTime = round(microtime(true) - $startTime, 2);
        Log::info("Finished processing {$this->filePath} in {$executionTime} seconds.");
    }

    private function insertElasticSearch($id, $data)
    {
        $client = ClientBuilder::create()->build();

        $params = [
            'index' => 'consumers',
            'id'    => $id,
            'body'  => $data
        ];

        $client->index($params);
    }

    private function updateElasticSearch($id, $data)
    {
        $client = ClientBuilder::create()->build();

        $params = [
            'index' => 'consumers',
            'id'    => $id,
            'body'  => [
                'doc' => $data
            ]
        ];

        $client->update($params);
    }
}
