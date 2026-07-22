<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fiscal_year_id',
        'title',
        'summary',
        'goals',
        'status',
        'approved_at',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}

