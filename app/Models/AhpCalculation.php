<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AhpCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'criteria_weights',
        'boarding_house_scores',
        'ranking',
        'consistency_ratio',
        'weight_method',
        'alternative_weights',        // <-- baru
        'alternatives_consistency'    // <-- baru
    ];

    protected $casts = [
        'criteria_weights' => 'array',
        'boarding_house_scores' => 'array',
        'ranking' => 'array',
        'alternative_weights' => 'array',        // <-- baru
        'alternatives_consistency' => 'array',  // <-- baru
        'consistency_ratio' => 'decimal:4'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}
