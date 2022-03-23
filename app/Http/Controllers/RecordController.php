<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Record;
use App\Models\User;
use Facade\FlareClient\Http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RecordController extends Controller
{
    public function type()
    {
        $user_id = Auth::user()->id;
        $total_count = Post::where('user_id', '=', $user_id)->count();
        $bike_count = Post::where('user_id', '=', $user_id)->where('event', '=', 'B')->count();

        if ($total_count != 0) {
            $bike_percentage = ($bike_count / $total_count) * 100;
            $run_percentage = 100 - $bike_percentage;
            return response([
                '자전거 비율' => $bike_percentage,
                '달리기 비율' => $run_percentage
            ], 200);
        } else {
            return response([
                'message' => '활동내역이 없습니다'
            ], 204);
        }
    }

    public function totalTime()
    {
        $user = Auth::user();
        $weekTime = 0;

        $time = Post::where('user_id', '=', $user->id)->get('time');
        $count = Post::where('user_id', '=', $user->id)->count();

        for ($i = 0; $i < $count; $i++) {
            $weekTime += $time[$i]->time;
        };

        if ($weekTime) {
            return response(
                $weekTime,
                200
            );
        } else {
            return response(
                '누적 시간이 없습니다',
                204
            );
        }
    }

    public function totalCalorie()
    {
        $user = Auth::user();
        $weekCalorie = 0;

        $calorie = Post::where('user_id', '=', $user->id)->get('calorie');
        $count = Post::where('user_id', '=', $user->id)->count();

        for ($i = 0; $i < $count; $i++) {
            $weekCalorie += $calorie[$i]->time;
        };

        if ($weekCalorie) {
            return response(
                $weekCalorie,
                200
            );
        } else {
            return response(
                '누적 칼로리가 없습니다',
                204
            );
        }
    }
}
