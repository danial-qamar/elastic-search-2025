<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;
use App\Models\Consumer;
use Illuminate\Support\Facades\Log;

class IndexConsumersToElasticsearch extends Command
{
    protected $signature = 'elasticsearch:index-consumers';
    protected $description = 'Bulk index 5000 Consumer records into Elasticsearch';

    public function handle()
    {
        $client = ClientBuilder::create()->build();

        $batchSize = 5000;
        $consumersCount = Consumer::count();
        $totalBatches = ceil($consumersCount / $batchSize);

        for ($i = 0; $i < $totalBatches; $i++) {
            $consumers = Consumer::skip($i * $batchSize)->take($batchSize)->get();

            $bulkParams = ['body' => []];

            foreach ($consumers as $consumer) {
                $bulkParams['body'][] = [
                    'index' => [
                        '_index' => 'consumers',
                        '_id' => $consumer->id,
                    ],
                ];
                $bulkParams['body'][] = $consumer->toArray();
            }

            if (!empty($bulkParams['body'])) {
                try {
                    $client->bulk($bulkParams);
                    $this->info("Batch " . ($i + 1) . " indexed successfully.");
                } catch (\Exception $e) {
                    $this->error("Error in batch " . ($i + 1) . ": " . $e->getMessage());
                }
            }
        }

        $this->info("Bulk indexing completed.");
    }
}
