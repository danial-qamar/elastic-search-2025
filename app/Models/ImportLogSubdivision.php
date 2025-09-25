<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLogSubdivision extends Model
{
    protected $fillable = [
        'import_log_id',
        'subdivision_code',
        'consumers_count',
        'indexed_count',
    ];

    public function log()
    {
        return $this->belongsTo(ImportLog::class);
    }
}
