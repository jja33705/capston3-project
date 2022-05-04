<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "mem_id",
        "target_mem_id",
        "not_type",
        "not_message",
        "read",
        "post_id"
    ];
}
