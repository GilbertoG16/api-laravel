<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class UserAchievement extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'achievement_id',
        // Otros campos relacionados con los logros
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
?>