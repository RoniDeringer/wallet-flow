<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    protected $table = 'job_batches';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'total_jobs',
        'pending_jobs',
        'failed_jobs',
        'failed_job_ids',
        'options',
        'cancelled_at',
        'created_at',
        'finished_at',
    ];
}

