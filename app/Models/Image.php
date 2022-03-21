<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        "post_id",
        "image"
    ];

    public function posts()
    {
        return $this->belongsTo(Post::class);
    }
}
