<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;

class DeleteSubdivision extends Command
{
    protected $signature = 'subdivision:delete {code : The subdivision code to delete}';
    protected $description = 'Delete a subdivision and its consumers from DB and Elasticsearch';

    public function handle()
    {
        $code = $this->argument('code');

        $client = ClientBuilder::create()->build();

        // Delete from Elasticsearch
        try {
            $client->deleteByQuery([
                'index' => 'consumers',
                'body'  => ['query' => ['term' => ['subdivision' => $code]]]
            ]);
            $this->info("✅ Elasticsearch records deleted for subdivision: {$code}");
        } catch (\Exception $e) {
            $this->warn("⚠️  Elasticsearch: " . $e->getMessage());
        }

        // Delete from DB
        $deleted = DB::table('consumers')->where('subdivision_code', $code)->delete();
        DB::table('subdivision')->where('code', $code)->delete();
        DB::table('import_log_subdivisions')->where('subdivision_code', $code)->delete();

        $this->info("✅ DB records deleted: {$deleted} consumers, subdivision '{$code}' removed.");
    }
}
