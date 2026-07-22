<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'course_id',
        'teacher_id',
        'session_number',
        'session_date',
        'starts_at',
        'ends_at',
        'student_count',
        'teaching_content',
        'learning_status',
        'teacher_note',
        'administrative_note',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}

