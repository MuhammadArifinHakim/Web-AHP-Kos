<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    use HasFactory;
    protected $table = 'criteria';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'order'
    ];

    public function subcriteria()
    {
        return $this->hasMany(Subcriteria::class)->orderBy('order');
    }

    public function boardingHouseValues()
    {
        return $this->hasMany(BoardingHouseCriteria::class);
    }
}