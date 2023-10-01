<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'last_name', 'identification', 'birth_date', 'profile_picture','user_id',
    ];


    public function user(){
        return $this->belongsTo(User::class);
    }
}
