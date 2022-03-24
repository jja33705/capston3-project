<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function track($id)
    {
        $track_id = $id;
        // return $query = Post::where('track_id', '=', $id)->orderBy('time', 'DESC')->get();
        return $query = Post::where('track_id', '=', $id)->distinct('user_id')->orderBy('time')->get();

        return $track_ranking = DB::table('posts')->where('');

        return $track_ranking;

        if ($track_ranking) {
            return response(
                $track_ranking,
                200
            );
        } else {
            return response(
                '랭킹이 없습니다',
                204
            );
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
