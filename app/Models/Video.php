<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;


    protected $fillable = [
        'video_url'
    ];

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }
}
