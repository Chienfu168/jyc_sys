<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'type',
        'city',
        'district',
        'address',
        'postal_code',
        'phone',
        'website',
        'student_count',
        'class_count',
        'category',
        'remote_status',
        'latitude',
        'longitude',
        'status',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SchoolContact::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}

