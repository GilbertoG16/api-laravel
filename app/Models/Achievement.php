<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = ['name','description','photo_url','achievement_type_id','id_asociacion'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($achievement) {
            $achievement->users()->detach();
        });
    }
    public function type() {
        return $this->belongsTo(Achievement_Type::class, 'achievement_type_id');
    }

    public function trivia() {
        return $this->belongsTo(Trivia::class, 'id_asociacion')->where('achievement_type_id', 1);
    }

    public function qrInfoAssociation() {
        return $this->belongsTo(QrInfoAssociation::class, 'id_asociacion')->where('achievement_type_id', 2);
    }

   /* public function rules()
    {
        return $this->belongsToMany(AchievementRule::class, 'achievement_rules')
            ->withTimestamps();
    }*/
}
