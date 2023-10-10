<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQrHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'qr_info_association_id',
    ];

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function qrInfoAssociation()
    {
        return $this->belongsTo(QRInfoAssociation::class);
    }
}
