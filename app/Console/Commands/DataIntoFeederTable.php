<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DataIntoFeederTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:data-into-feeder-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feedersWithCount = DB::table('consumers')
            ->select(DB::raw("CASE WHEN feeder_name IS NULL OR feeder_name = '' THEN 'NULL/Empty' ELSE feeder_name END as feeder_name"), DB::raw('COUNT(*) as count'))
            ->groupBy('feeder_name')
            ->orderBy('count', 'desc')
            ->get();

        $this->info("Feeder names with counts (NULL or empty grouped):");
        foreach ($feedersWithCount as $feeder) {
            $this->line("{$feeder->feeder_name}: {$feeder->count}");
        }
    }
}
