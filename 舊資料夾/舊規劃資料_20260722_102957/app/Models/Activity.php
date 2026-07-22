<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fiscal_year_id',
        'project_id',
        'school_id',
        'name',
        'type',
        'activity_date',
        'starts_at',
        'ends_at',
        'location',
        'city',
        'district',
        'organizer',
        'co_organizer',
        'contact_person',
        'student_count',
        'teacher_count',
        'volunteer_count',
        'guest_count',
        'total_participants',
        'budget_amount',
        'actual_cost',
        'description',
        'status',
        'note',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(Outcome::class);
    }
}

