<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RankingController extends Controller
{
    //private빼기
    public function track(Request $request)
    {
        $track_id = $request->query();
        $rank = Post::with('user')->where('track_id', '=', $track_id)->where('range', '=', 'public')->orderby('time')->get();
        if ($rank) {
            return response(
                ['ranking' => $rank],
                200
            );
        } else {
            return response(['message' => '해당 트랙을 달린 유저가 존재하지 않습니다']);
        }
    }


    public function myRank(Request $request)
    {
        $track_id = $request->query('track_id');
        $user = Auth::user();

        $post = Post::where('track_id', '=', $track_id)->where('user_id', '=', $user->id)->orderby('time')->first('time');

        if ($post) {
            return response([
                "user" => $user,
                "rank" => Post::where('track_id', '=', $track_id)->where('time', '<=', $post->time)->count()
            ], 200);
        } else {
            return response([
                'message' => '기록이 존재하지 않습니다.'
            ], 200);
        }
    }

    //전체 mmr랭킹
    public function mmr()
    {
        return response(
            User::orderby('mmr', 'desc')->get(),
            200
        );
    }
}
