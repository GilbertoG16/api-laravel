<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrInfoAssociation extends Model
{
    use HasFactory;

    protected $fillabe = [
        'latitude',
        'longitude',
        'qr_identifier',
        'location_id'
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
}
