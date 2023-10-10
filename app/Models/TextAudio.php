<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextAudio extends Model
{
    use HasFactory;

    protected $fillabe = [
        'text',
        'audio_url'
    ];

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }
}
