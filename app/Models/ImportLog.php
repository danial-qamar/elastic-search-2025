<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'bill_month',
        'consumers_count',
        'subdivisions_count',
        'indexed_count',
        'duration',
    ];

    public function subdivisions()
    {
        return $this->hasMany(ImportLogSubdivision::class);
    }
}
