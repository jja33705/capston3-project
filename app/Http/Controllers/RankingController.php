<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function track($id)
    {
        $track_id = $id;
        return response(
            Post::orderby('time')->where('track_id', '=', $track_id)->get('user_id'),
            200
        );
    }

    public function mmr()
    {
        return response(
            User::orderby('mmr', 'desc')->get('id'),
            200
        );
    }
}
