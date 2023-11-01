<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permission_required',
    ];
    
    public function qrInfoAssociations()
    {
        return $this->hasMany(QrInfoAssociation::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function unauthorizedAccesses()
    {
        return $this->hasMany(UnauthorizedAccess::class);
    }
}
