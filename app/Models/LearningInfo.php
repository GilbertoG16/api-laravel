<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'description',
    ];

    public function qrInfoAssociations()
    {
        return $this->hasMany(QrInfoAssociation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function videos()
    {
        return $this->hasOne(Video::class); 
    }
    
    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function text_audios()
    {
        return $this->hasMany(TextAudio::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function trivias()
    {
        return $this->hasOne(Trivia::class);
    }
}
