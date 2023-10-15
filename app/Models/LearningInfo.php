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
        return $this->hasOne(TextAudio::class);
    }
        
}
