<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fiscal_year_id',
        'project_id',
        'category',
        'budget_amount',
        'actual_amount',
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
}

