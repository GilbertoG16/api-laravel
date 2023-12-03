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
    public function type() {
        return $this->belongsTo(Achievement_Type::class, 'achievement_type_id');
    }

    // Relación con la tabla 'trivia'
    public function trivia() {
        return $this->belongsTo(Trivia::class, 'id_asociacion')->where('achievement_type_id', 1);
    }

    // Relación con la tabla 'qr_info_associations'
    public function qrInfoAssociation() {
        return $this->belongsTo(QrInfoAssociation::class, 'id_asociacion')->where('achievement_type_id', 2);
    }

   /* public function rules()
    {
        return $this->belongsToMany(AchievementRule::class, 'achievement_rules')
            ->withTimestamps();
    }*/
}
