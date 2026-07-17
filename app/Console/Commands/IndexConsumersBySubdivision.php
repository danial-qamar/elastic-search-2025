<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Elasticsearch\ClientBuilder;
use App\Models\Consumer;

class IndexConsumersBySubdivision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:index-consumers-by-subdivision {bill_month?} {--truncate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index consumers into Elasticsearch subdivision by subdivision from the consumers table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        // Detect bill_month automatically if not passed as an argument
        $billMonth = $this->argument('bill_month');
        if (!$billMonth) {
            $billMonth = DB::table('consumers')->whereNotNull('bill_month')->value('bill_month');
        }

        if (!$billMonth) {
            $this->error("❌ Could not detect bill_month. Please pass it as an argument.");
            return;
        }

        $this->info("🚀 Starting indexing for bill_month: {$billMonth}");

        // Handle truncate option (clear logs only for this bill month, NOT the consumers table)
        if ($this->option('truncate')) {
            $logIds = DB::table('import_logs')->where('bill_month', $billMonth)->pluck('id');
            if ($logIds->isNotEmpty()) {
                DB::table('import_log_subdivisions')->whereIn('import_log_id', $logIds)->delete();
                DB::table('import_logs')->whereIn('id', $logIds)->delete();
                $this->info("🗑️ import_logs and import_log_subdivisions cleared for bill_month {$billMonth}.");
            }
        }

        // Ensure we have an import_logs record
        DB::table('import_logs')->updateOrInsert(
            ['bill_month' => $billMonth],
            [
                'consumers_count'    => 0,
                'subdivisions_count' => 0,
                'indexed_count'      => 0,
                'updated_at'         => now(),
                'created_at'         => now(),
            ]
        );

        $importLogId = DB::table('import_logs')->where('bill_month', $billMonth)->value('id');

        // Fetch unique subdivision codes from consumers table.
        $subdivisions = DB::table('consumers')
            ->whereNotNull('subdivision_code')
            ->where('subdivision_code', '<>', '')
            ->distinct()
            ->pluck('subdivision_code');

        if ($subdivisions->isEmpty()) {
            $this->error("❌ No subdivisions found in consumers table.");
            return;
        }

        $this->info("Found " . $subdivisions->count() . " subdivision(s) to process.");

        $client = ClientBuilder::create()->build();

        // Ensure the Elasticsearch index exists with mappings
        try {
            if (!$client->indices()->exists(['index' => 'consumers'])) {
                $this->info("Creating Elasticsearch index 'consumers' with mappings...");
                $consumerModel = new Consumer();
                $consumerModel->createIndexWithMappings();
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Could not check/create Elasticsearch index: " . $e->getMessage());
        }

        $totalIndexed = 0;
        $totalProcessedSubdivisions = 0;

        foreach ($subdivisions as $subCode) {
            $subStart = microtime(true);
            $this->info("Processing subdivision: {$subCode}");

            // 1. Delete old index for this subdivision in Elasticsearch
            try {
                $client->deleteByQuery([
                    'index' => 'consumers',
                    'body'  => [
                        'query' => [
                            'term' => ['subdivision' => $subCode]
                        ]
                    ]
                ]);
                $this->info("  🗑️ Old Elasticsearch index cleared for subdivision: {$subCode}");
            } catch (\Exception $e) {
                $this->warn("  ⚠️ No previous index found / error deleting for subdivision {$subCode}: " . $e->getMessage());
            }

            // 2. Count consumers for this subdivision.
            $countForSubdivision = DB::table('consumers')
                ->where('subdivision_code', $subCode)
                ->count();

            $this->info("  Total consumers in DB for subdivision {$subCode}: {$countForSubdivision}");

            $indexedThisSubdivision = 0;
            $batchSize = 5000;

            // 3. Index in chunks of 5000
            try {
                Consumer::where('subdivision_code', $subCode)
                    ->chunkById($batchSize, function ($batch) use ($client, $subCode, &$indexedThisSubdivision, &$totalIndexed) {
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

                        if (!empty($bulkParams['body'])) {
                            $client->bulk($bulkParams);
                            $indexedCount = $batch->count();
                            $indexedThisSubdivision += $indexedCount;
                            $totalIndexed += $indexedCount;
                        }
                    });

                // 4. Update import_log_subdivisions log for this subdivision
                DB::table('import_log_subdivisions')->updateOrInsert(
                    [
                        'import_log_id'    => $importLogId,
                        'subdivision_code' => $subCode,
                    ],
                    [
                        'consumers_count' => $countForSubdivision,
                        'indexed_count'   => $indexedThisSubdivision,
                        'updated_at'      => now(),
                    ]
                );

                $subTime = round(microtime(true) - $subStart, 2);
                $this->info("  ✅ Subdivision {$subCode} completed: Indexed {$indexedThisSubdivision} records in {$subTime}s");
                $totalProcessedSubdivisions++;

            } catch (\Exception $e) {
                Log::error("Error indexing subdivision {$subCode}: " . $e->getMessage());
                $this->error("  ❌ Error indexing subdivision {$subCode}: " . $e->getMessage());
            }

            $this->line(str_repeat('-', 60));
        }

        // Update main import_logs stats
        $totalTime = round(microtime(true) - $startTime, 2);
        $importLog = DB::table('import_logs')->where('id', $importLogId)->first();
        $durationToSave = $totalTime;
        if (!$this->option('truncate') && $importLog) {
            $durationToSave = $importLog->duration + $totalTime;
        }

        DB::table('import_logs')
            ->where('id', $importLogId)
            ->update([
                'consumers_count'    => DB::table('import_log_subdivisions')
                                            ->where('import_log_id', $importLogId)
                                            ->sum('consumers_count'),
                'subdivisions_count' => DB::table('import_log_subdivisions')
                                            ->where('import_log_id', $importLogId)
                                            ->count(),
                'indexed_count'      => DB::table('import_log_subdivisions')
                                            ->where('import_log_id', $importLogId)
                                            ->sum('indexed_count'),
                'duration'           => $durationToSave,
                'updated_at'         => now(),
            ]);

        $this->info("📊 Indexing Summary:");
        $this->info("   Total subdivisions processed: {$totalProcessedSubdivisions}");
        $this->info("   Total records indexed: {$totalIndexed}");
        $this->info("   Total time: {$totalTime}s");
    }
}
