<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail 
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hasAnyRole($roles)
    {
        // Verifica si el usuario tiene al menos uno de los roles especificados
        return $this->roles->pluck('name')->intersect($roles)->count() > 0;
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmailNotification);
    }
    
   
    public function roles(){
        return $this->belongsToMany(Role::class, 'user_roles', 'userId', 'roleId');
    }

    public function profile(){
        return $this->hasOne(Profile::class);
    }

    public function userQrHistories()
    {
        return $this->hasMany(UserQrHistory::class);
    }

    public function answers()
    {   
        return $this->belongsToMany(Answer::class, 'user_answers')->withPivot('is_correct');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function unauthorizedAccesses()
    {
        return $this->hasMany(UnauthorizedAccess::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievement')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

}
