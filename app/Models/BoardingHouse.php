<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardingHouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'price',
        'image'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'boarding_house_campus')
                    ->withPivot('distance')
                    ->withTimestamps();
    }

    public function criteriaValues()
    {
        return $this->hasMany(BoardingHouseCriteria::class);
    }

    public function getDistanceToCampus($campusId)
    {
        $campus = $this->campuses()->where('campus_id', $campusId)->first();
        return $campus ? $campus->pivot->distance : null;
    }

    
}