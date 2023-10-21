<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trivia extends Model
{
    use HasFactory;

    protected $table = 'trivias';

    protected $fillable = [
        'name',
        'description',
        'learning_info_id',
    ];

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}