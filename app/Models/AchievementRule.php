<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementRule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description','sql_condition'];

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class);
    }
}
