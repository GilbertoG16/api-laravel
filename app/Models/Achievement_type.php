<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement_type extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo'
    ];
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class);
    }
}
