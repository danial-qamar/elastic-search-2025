<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerHistory extends Model
{
    protected $fillable = [
        'consumer_id',
        'updated_by',
        'changed_fields',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
