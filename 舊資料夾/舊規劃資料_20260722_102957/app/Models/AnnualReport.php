<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fiscal_year_id',
        'title',
        'work_summary',
        'financial_summary',
        'outcome_summary',
        'status',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}

