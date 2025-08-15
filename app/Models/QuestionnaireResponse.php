<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'pairwise_values',
        'consistency_ratio',
        'source'
    ];

    protected $casts = [
        'pairwise_values' => 'array',
        'consistency_ratio' => 'decimal:4'
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}