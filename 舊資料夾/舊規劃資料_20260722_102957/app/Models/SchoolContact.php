<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'title',
        'phone',
        'mobile',
        'email',
        'line_id',
        'note',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}

