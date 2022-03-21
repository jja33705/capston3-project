<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "Sun",
        "Mon",
        "Tue",
        "Wed",
        "Tur",
        "Fri",
        "Sat"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
