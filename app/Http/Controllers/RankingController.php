<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RankingController extends Controller
{
    public function track(Request $request)
    {
        $track_id = $request->query();
        $rank = Post::with('user')->where('track_id', '=', $track_id)->orderby('time')->get();
        if ($rank) {
            return response(
                ['ranking' => $rank],
                200
            );
        } else {
            return response(['message' => '해당 트랙을 달린 유저가 존재하지 않습니다']);
        }
    }

    public function mmr()
    {
        return response(
            User::orderby('mmr', 'desc')->get('id'),
            200
        );
    }
}
