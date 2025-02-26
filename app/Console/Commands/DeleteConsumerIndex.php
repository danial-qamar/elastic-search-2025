<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;

class DeleteConsumerIndex extends Command
{
    protected $signature = 'elasticsearch:delete-consumer-index';
    protected $description = 'Delete the Elasticsearch index for consumers';

    public function handle()
    {
        $client = ClientBuilder::create()->build();

        $indexName = 'consumers';

        try {
            $indexExists = $client->indices()->exists(['index' => $indexName]);

            if ($indexExists) {
                $client->indices()->delete(['index' => $indexName]);
                $this->info("Index '$indexName' deleted successfully.");
            } else {
                $this->info("Index '$indexName' does not exist.");
            }
        } catch (\Exception $e) {
            $this->error("Error deleting index: " . $e->getMessage());
        }
    }
}
