<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Consumer;
use Elasticsearch\ClientBuilder;
use Throwable;
use Illuminate\Support\Str;

class ImportAndIndexConsumers implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $trackingId;
    protected $code;
    protected $startTime;
    private $stepStart;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $trackingId = null)
    {
        $this->filePath   = storage_path('app/' . $filePath);
        $this->trackingId = $trackingId ?? Str::uuid()->toString();

        $filename = pathinfo($this->filePath, PATHINFO_FILENAME);

        if (preg_match('/^([A-Z]+)\s*(\d{5})$/i', $filename, $matches)) {
            $this->code = $matches[2];
        } else {
            throw new \Exception("Invalid file name format: {$filename}");
        }

        $this->startTime = microtime(true);

        Cache::put($this->cacheKey(), [
            'status'         => 'running',
            'messages'       => [],
            'total_imported' => 0,
            'total_batches'  => 0,
            'elapsed_time'   => null
        ], now()->addMinutes(15));

        // Initialize step timer
        $this->stepStart = microtime(true);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $client = ClientBuilder::create()->build();

        $this->addLog("📥 Starting import for subdivision code: {$this->code}", false);

        DB::beginTransaction();
        try {
            $this->startStep();
            DB::table('consumers')
                ->whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$this->code])
                ->delete();
            $this->addLog("🗑 Deleted existing consumers for subdivision {$this->code}");

            $this->startStep();
            $query = "LOAD DATA LOCAL INFILE '" . addslashes($this->filePath) . "' INTO TABLE consumers
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

            DB::unprepared($query);
            $countImported = DB::table('consumers')
                ->whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$this->code])
                ->count();
            $this->addLog("✅ Imported " . $this->prettyNumber($countImported) . " consumers");

            $this->startStep();
            try {
                $client->deleteByQuery([
                    'index' => 'consumers',
                    'body'  => [
                        'query' => [
                            'term' => ['subdivision' => $this->code]
                        ]
                    ]
                ]);
                $this->addLog("🗑 Deleted old Elasticsearch data for subdivision {$this->code}");
            } catch (\Exception $e) {
                $this->addLog("⚠️ No old Elasticsearch index found for {$this->code}");
            }

            $this->startStep();
            $batchSize = 5000;
            $totalBatches = ceil($countImported / $batchSize);
            $indexedCount = 0;

            for ($i = 0; $i < $totalBatches; $i++) {
                $consumers = Consumer::whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$this->code])
                    ->skip($i * $batchSize)
                    ->take($batchSize)
                    ->get();

                $bulkParams = ['body' => []];
                foreach ($consumers as $consumer) {
                    $data = $consumer->toArray();
                    $data['subdivision'] = $this->code;

                    $bulkParams['body'][] = [
                        'index' => [
                            '_index' => 'consumers',
                            '_id'    => $consumer->id,
                        ],
                    ];
                    $bulkParams['body'][] = $data;
                }

                if (!empty($bulkParams['body'])) {
                    $client->bulk($bulkParams);
                    $indexedCount += count($consumers);
                    $this->addLog("📦 Indexed batch " . ($i + 1) . " of {$totalBatches} → " . $this->prettyNumber($indexedCount) . " total");
                }
            }

            DB::commit();

            $timeTaken = round(microtime(true) - $this->startTime, 2) . 's';
            $this->finishLog($countImported, $totalBatches, $timeTaken);

        } catch (Throwable $e) {
            DB::rollBack();
            $this->failLog($e->getMessage());
            Log::error($e);
        }
    }

    /**
     * Helper: start a step timer.
     */
    private function startStep()
    {
        $this->stepStart = microtime(true);
    }

    /**
     * Format large numbers with commas.
     */
    private function prettyNumber($num)
    {
        return number_format($num);
    }

    /**
     * Store a log message in cache.
     * If $withTime is true, appends elapsed time since last step.
     */
    protected function addLog($message, $withTime = true)
    {
        $summary = Cache::get($this->cacheKey());
        if (!$summary) {
            $summary = [
                'status'         => 'running',
                'messages'       => [],
                'total_imported' => 0,
                'total_batches'  => 0,
                'elapsed_time'   => null,
            ];
        }

        if ($withTime && $this->stepStart) {
            $elapsed = round(microtime(true) - $this->stepStart, 2);
            $message .= " ({$elapsed}s)";
        }

        $summary['messages'][] = "[" . now()->format('H:i:s') . "] " . $message;
        Cache::put($this->cacheKey(), $summary, now()->addMinutes(15));

        // reset timer for next step
        $this->stepStart = microtime(true);
    }

    protected function finishLog($totalImported, $totalBatches, $timeTaken)
    {
        $summary = Cache::get($this->cacheKey());
        $summary['status']         = 'completed';
        $summary['total_imported'] = $totalImported;
        $summary['total_batches']  = $totalBatches;
        $summary['elapsed_time']   = $timeTaken;

        $this->addLog("🏁 Completed import & indexing in {$timeTaken} — " . $this->prettyNumber($totalImported) . " consumers processed", false);
        Cache::put($this->cacheKey(), $summary, now()->addMinutes(15));
    }

    protected function failLog($errorMessage)
    {
        $summary = Cache::get($this->cacheKey());
        $summary['status'] = 'failed';
        $this->addLog("❌ Error: {$errorMessage}");
        Cache::put($this->cacheKey(), $summary, now()->addMinutes(15));
    }

    protected function cacheKey()
    {
        return "import_summary_{$this->trackingId}";
    }
}
