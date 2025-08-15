<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardingHouseCriteria extends Model
{
    use HasFactory;

    protected $table = 'boarding_house_criteria';

    protected $fillable = [
        'boarding_house_id',
        'criteria_id',
        'values'
    ];

    protected $casts = [
        'values' => 'array'
    ];

    public function boardingHouse()
    {
        return $this->belongsTo(BoardingHouse::class);
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }
}