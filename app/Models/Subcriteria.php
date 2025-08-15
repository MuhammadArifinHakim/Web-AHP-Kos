<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcriteria extends Model
{
    use HasFactory;
    protected $table = 'subcriteria';
    protected $fillable = [
        'criteria_id',
        'name',
        'code',
        'type',
        'description',
        'order'
    ];

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }
}