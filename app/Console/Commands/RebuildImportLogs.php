<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildImportLogs extends Command
{
    protected $signature = 'app:rebuild-import-logs {bill_month?}';
    protected $description = 'Rebuild import_logs and import_log_subdivisions from existing consumers table.';

    public function handle()
    {
        $this->info("🔄 Rebuilding import logs...");

        // Detect bill_month automatically if not passed
        $billMonth = $this->argument('bill_month');
        if (!$billMonth) {
            $billMonth = DB::table('consumers')->value('bill_month');
        }

        if (!$billMonth) {
            $this->error("❌ Could not detect bill_month. Pass it as an argument.");
            return;
        }

        // Create or update import_logs entry
        $importLogId = DB::table('import_logs')->updateOrInsert(
            ['bill_month' => $billMonth],
            [
                'consumers_count'    => 0,
                'subdivisions_count' => 0,
                'indexed_count'      => 0,
                'duration'           => 0,
                'updated_at'         => now(),
                'created_at'         => now(),
            ]
        );

        // Fetch the ID of the created/updated record
        $importLogId = DB::table('import_logs')->where('bill_month', $billMonth)->value('id');

        // Reset subdivision logs for this bill_month
        DB::table('import_log_subdivisions')->where('import_log_id', $importLogId)->delete();

        // Build subdivision stats
        $subdivisionData = DB::table('consumers')
            ->select('subdivision_code', DB::raw('COUNT(*) as consumers_count'))
            ->groupBy('subdivision_code')
            ->get();

        $totalConsumers = 0;
        $subdivisionRows = [];

        foreach ($subdivisionData as $row) {
            $totalConsumers += $row->consumers_count;
            if (!preg_match('/^\d{5}$/', $row->subdivision_code)) {
                $this->warn("⚠️ Skipping invalid subdivision code: {$row->subdivision_code}");
                continue;
            }
            $subdivisionRows[] = [
                'import_log_id'    => $importLogId,
                'subdivision_code' => $row->subdivision_code,
                'consumers_count'  => $row->consumers_count,
                'indexed_count'    => $row->consumers_count, // we can’t rebuild this unless we check ES
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // Insert subdivision logs
        DB::table('import_log_subdivisions')->insert($subdivisionRows);

        // Update parent log
        DB::table('import_logs')->where('id', $importLogId)->update([
            'consumers_count'    => $totalConsumers,
            'subdivisions_count' => count($subdivisionRows),
            'indexed_count'      => array_sum(array_column($subdivisionRows, 'indexed_count')),
            'updated_at'         => now(),
        ]);

        $this->info("✅ Rebuilt import log for bill_month {$billMonth}");
        $this->info("   Total consumers: {$totalConsumers}");
        $this->info("   Total subdivisions: " . count($subdivisionRows));
    }
}
