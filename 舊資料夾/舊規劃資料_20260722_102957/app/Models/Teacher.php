<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'gender',
        'mobile',
        'email',
        'address',
        'education',
        'experience',
        'professional_field',
        'teaching_specialty',
        'introduction',
        'bank_name',
        'bank_branch',
        'bank_account',
        'account_name',
        'hourly_rate',
        'session_rate',
        'transportation_fee',
        'special_rate_note',
        'status',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }
}

