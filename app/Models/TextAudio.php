<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextAudio extends Model
{
    use HasFactory;
    protected $table = 'text_audios';

    protected $fillable = [
        'text',
        'audio_url'
    ];

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }
}
