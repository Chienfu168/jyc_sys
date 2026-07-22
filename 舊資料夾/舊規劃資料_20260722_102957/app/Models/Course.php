<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'school_id',
        'teacher_id',
        'name',
        'semester',
        'assistant_teacher',
        'total_weeks',
        'hours_per_session',
        'participant_count',
        'starts_on',
        'ends_on',
        'description',
        'teaching_goal',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }
}

