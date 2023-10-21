<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'trivia_id',
        'question_text',

    ];

    public function trivias()
    {
        return $this->belongsTo(Trivia::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function imageQuestion()
    {
        return $this->hasOne(ImageQuestion::Class);
    }
}
