<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapImage extends Model
{
    use HasFactory;

    protected $fillable = [
        "post_id",
        "image",
        "url"
    ];

    public function posts()
    {
        return $this->belongsTo(Post::class);
    }
}
