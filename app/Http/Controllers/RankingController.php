<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function track(Request $request)
    {
        $track_id = $request->track_id;
        return Post::orderby('time')->where('track_id', '=', $track_id)->get('user_id');
    }

    public function mmr()
    {
        return User::orderby('mmr', 'desc')->get('id');
    }
}
