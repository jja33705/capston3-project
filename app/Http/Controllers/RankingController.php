<?php

namespace App\Http\Controllers;

use App\Models\Follow;
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

        $query = DB::table('posts')->where('track_id', '=', $track_id)->where('kind', '=', '랭크')->select('user_id', DB::raw('MIN(time) as time'))->groupBy('user_id')->orderBy('time')->get();

        $data = array();
        $data2 = array();

        for ($i = 0; $i < count($query); $i++) {
            array_push($data, Post::where('user_id', '=', $query[$i]->user_id)->where('time', '=', $query[$i]->time)->first('id'));
            array_push($data2, $data[$i]->id);
        }

        $rank = Post::with('user')->whereIn('id', $data2)->orderBy('time')->paginate(10);

        if ($data) {
            return response(
                $rank,
                200
            );
        } else {
            return response([
                'data' => [
                    'message' => '해당 트랙을 달린 유저가 존재하지 않습니다'
                ]
            ]);
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

    public function followRank()
    {
        $user = Auth::user();
        $data = Follow::where('follower_id', '=', $user->id)->get('following_id');

        $followings = array();

        for ($i = 0; $i < count($data); $i++) {
            array_push($followings, $data[$i]->following_id);
        }
        array_push($followings, $user->id);

        return User::whereIn('id', $followings)->orderBy('mmr', 'desc')->get();
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
