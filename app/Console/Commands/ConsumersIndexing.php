<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;
use App\Models\Consumer;
use Illuminate\Support\Facades\DB;

class ConsumersIndexing extends Command
{
    protected $signature = 'app:consumers-indexing';
    protected $description = 'This will cache the consumers data in the Elasticsearch';

    public function handle()
    {
        $startTime = microtime(true);
        $totalIndexed = 0;

        $client = ClientBuilder::create()->build();
        $subdivisions = DB::table('subdivision')->get();

        foreach ($subdivisions as $sub) {
            $subdivisionStart = microtime(true);
            $subCode = $sub->code;

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

            $consumersCount = Consumer::whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$subCode])->count();
            $this->info("Subdivision $subCode - Total consumers in DB: $consumersCount");

            $batchSize = 5000;
            $offset = 0;
            $indexedThisSubdivision = 0;
            $batchNumber = 1;

            while (true) {
                $batchStart = microtime(true);

                $consumers = Consumer::whereRaw("SUBSTRING(reference_no, 3, 5) = ?", [$subCode])
                    ->skip($offset)
                    ->take($batchSize)
                    ->get();

                if ($consumers->isEmpty()) {
                    break;
                }

                $bulkParams = ['body' => []];

                foreach ($consumers as $consumer) {
                    $consumerData = $consumer->toArray();
                    $consumerData['subdivision'] = $subCode;

                    $bulkParams['body'][] = [
                        'index' => [
                            '_index' => 'consumers',
                            '_id' => $consumer->id,
                        ],
                    ];
                    $bulkParams['body'][] = $consumerData;
                }

                $client->bulk($bulkParams);

                $indexedCount = $consumers->count();
                $indexedThisSubdivision += $indexedCount;
                $totalIndexed += $indexedCount;

                $batchTime = round(microtime(true) - $batchStart, 2);
                $this->info("Subdivision $subCode - Batch {$batchNumber} indexed ({$indexedCount} records) in {$batchTime}s");

                $offset += $batchSize;
                $batchNumber++;

                if ($indexedCount < $batchSize) {
                    break;
                }
            }

            $subdivisionTime = round(microtime(true) - $subdivisionStart, 2);
            $this->info("✅ Subdivision $subCode completed: Indexed {$indexedThisSubdivision} / {$consumersCount} in {$subdivisionTime}s");
            $this->line(str_repeat('-', 60));
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->info("📍 Total subdivisions processed: " . count($subdivisions));
        $this->info("⚡ Average speed: " . round($totalIndexed / max($totalTime, 1), 2) . " records/sec");
        $this->info("📊 Total records indexed: $totalIndexed");

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

        $this->info("⏱ Total time: {$formattedTime}");
    }
}
