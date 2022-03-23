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
        $track_ranking = Post::orderby('time')->where('track_id', '=', $track_id)->get('user_id');

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
