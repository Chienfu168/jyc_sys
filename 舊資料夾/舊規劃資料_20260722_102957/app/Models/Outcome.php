<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outcome extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'activity_id',
        'title',
        'description',
        'participant_count',
        'result_summary',
        'school_feedback',
        'teacher_feedback',
        'student_feedback',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}

