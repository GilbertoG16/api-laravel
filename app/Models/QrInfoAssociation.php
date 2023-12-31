<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrInfoAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'latitude',
        'longitude',
        'qr_identifier',
        'location_id',
        'learning_info_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function learningInfo()
    {
        return $this->belongsTo(LearningInfo::class);
    }

    public function userQrHistories()
    {
        return $this->hasMany(UserQrHistory::class);
    }
    public function achievements()
    {
        return $this->hasMany(Achievement::class, 'id_asociacion');
    }
}
