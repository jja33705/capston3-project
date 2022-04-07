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

        // return $query = DB::table('posts')->where('track_id', '=', $track_id)->

        $query = DB::table('posts')->where('track_id', '=', $track_id)->where('kind', '=', '랭크')->select('user_id', DB::raw('MIN(time) as time'))->groupBy('user_id')->orderBy('time')->get();

        $rank = array();

        for ($i = 0; $i < count($query); $i++) {
            array_push($rank, $i + 1, Post::where('user_id', '=', $query[$i]->user_id)->where('time', '=', $query[$i]->time)->first());
        }
        return $rank;



        // return $query[1]->user_id;




        return $query = DB::table('posts')->select('time', 'user_id')->where('track_id', '=', $track_id)
            ->where('range', '=', 'public')->groupBy('time')->orderBy('time')->get();


        return $rank = Post::where('track_id', '=', $track_id)->where('range', '=', 'public')->orderby('time')->get('user_id');

        if ($rank) {
            return response(
                ['ranking' => $rank],
                200
            );
        } else {
            return response(['message' => '해당 트랙을 달린 유저가 존재하지 않습니다'], 200);
        }
    }


    public function myRank(Request $request)
    {
        $track_id = $request->query('track_id');
        $user = Auth::user();

        $post = Post::where('track_id', '=', $track_id)->where('user_id', '=', $user->id)->orderby('time')->first();

        if ($post) {
            return response([
                "post" => $post,
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
