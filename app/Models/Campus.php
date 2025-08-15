<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'image',
        'description'
    ];

    public function boardingHouses()
    {
        return $this->belongsToMany(BoardingHouse::class, 'boarding_house_campus')
                    ->withPivot('distance')
                    ->withTimestamps();
    }

    public function questionnaireResponses()
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }

    public function ahpCalculations()
    {
        return $this->hasMany(AhpCalculation::class);
    }
}