<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'start_event', 'learning_info_id','end_event'];

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }
}
