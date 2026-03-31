<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;

class DeleteSubdivision extends Command
{
    protected $signature = 'subdivision:delete {code : First 3 digits of the subdivision code}';
    protected $description = 'Delete subdivisions (matched by first 3 digits) and their consumers from DB and Elasticsearch';

    public function handle()
    {
        $prefix = $this->argument('code');

        if (strlen($prefix) !== 3 || !ctype_digit($prefix)) {
            $this->error('❌ Code must be exactly 3 digits, e.g. 264');
            return;
        }

        // Fetch all matching full 5-digit codes from DB
        $codes = DB::table('subdivision')->where('code', 'like', "{$prefix}%")->pluck('code');

        if ($codes->isEmpty()) {
            $this->warn("⚠️  No subdivisions found with prefix: {$prefix}");
            return;
        }

        $this->info("Found subdivisions: " . $codes->implode(', '));

        $client = ClientBuilder::create()->build();

        // Delete from Elasticsearch by exact term for each full code
        foreach ($codes as $code) {
            try {
                $client->deleteByQuery([
                    'index' => 'consumers',
                    'body'  => ['query' => ['term' => ['subdivision' => $code]]]
                ]);
                $this->info("✅ ES deleted for subdivision: {$code}");
            } catch (\Exception $e) {
                $this->warn("⚠️  ES [{$code}]: " . $e->getMessage());
            }
        }

        // Delete from DB
        $deleted = DB::table('consumers')->where('subdivision_code', 'like', "{$prefix}%")->delete();
        DB::table('subdivision')->where('code', 'like', "{$prefix}%")->delete();
        DB::table('import_log_subdivisions')->where('subdivision_code', 'like', "{$prefix}%")->delete();

        $this->info("✅ DB deleted: {$deleted} consumers, subdivisions with prefix '{$prefix}' removed.");
    }
}
