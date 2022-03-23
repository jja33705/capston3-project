<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "event",
        "time",
        "title",
        "calorie",
        "average_speed",
        "altitude",
        "distance",
        "img",
        "content",
        "range",
        "track_id",
        "gps_id",
        "mmr",
        "kind",
        "date",
        "opponent_id"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes', 'post_id', 'user_id', 'id', 'id', 'users');  //외래키를 적어야 하지만 관례를 따라서 생략 가능
    }

    public function comment()
    {
        return $this->hasMany(Comment::class);
    }
}
