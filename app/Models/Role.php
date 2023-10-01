<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name'
    ];

    public $timestamps = false; // No hace falta saber cuando se crearon los roles 😍

    public static function getAllRoles(){
        return self::all('id', 'name');
    }

    // Relación de muchos a muchos con User 😘
    public function users(){
        return $this->belongsToMany(User::class, 'user_roles', 'roleId', 'userId');
    }
}
