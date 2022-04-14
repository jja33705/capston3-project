<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    public function goal(Request $request)
    {
        //날짜 입력 받기
        //그 날짜 범위의 활동만 주행거리로 누적
        //날짜 지정
        $title = $request->title;
        $goalDistance = $request->goal;
        $firstDate = $request->firstDate;
        $lastDate = $request->lastDate;

        $user = Auth::user();
        //목표 생성
        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => $title,
            'goalDistance' => $goalDistance,
            'firstDate' => $firstDate,
            'lastDate' => $lastDate,
            'success' => false
        ]);

        if ($goal) {
            return response($goal, 201);
        } else {
            return response('', 400);
        }
    }

    public function checkGoal()
    {
        //현재시간
        $today = date('Y-m-d', time());
        $user = Auth::user();

        $goal = Goal::where('user_id', '=', $user->id)->where('firstDate', '<=', $today)->where('lastDate', '>=', $today)->get();

        for ($i = 0; $i < count($goal); $i++) {
            $post = Post::where('user_id', '=', $user->id)->where('date', '>=', $goal[$i]['firstDate'])->where('date', '<=', $goal[$i]['lastDate'])->orderby('date')->get();
        }

        return $post;

        // for ($i = 0; $i < count($goal); $i++) {
        // }


        //목표 기간내 주행한 누적거리
        $distance = 0;
        for ($i = 0; $i < count($post); $i++) {
            $distance += $post[$i]->distance;
        }


        for ($i = 0; $i < count($goal); $i++) {
            if ($distance >= $goal[$i]['goalDistance']) {
                Goal::where('id', '=', $goal[$i]['id'])->update(['success' => true]);
            }
        }

        if ($distance) {
            return response($distance, 200);
        } else {
            return response('', 204);
        }
    }

    public function successGoal()
    {
        $user = Auth::user();
        $goal = Goal::where('user_id', '=', $user->id)->where('success', '=', 1)->orderby('firstDate')->get();

        if ($goal) {
            return response($goal, 200);
        } else {
            return response('', 204);
        }
    }

    public function progressGoal()
    {
        $today = date('Y-m-d', time());
        $user = Auth::user();

        $goal = Goal::where('user_id', '=', $user->id)->where('success', '=', 0)->where('firstDate', '<=', $today)->where('lastDate', '>=', $today)->orderby('firstDate')->get();

        if ($goal) {
            return response($goal, 200);
        } else {
            return response('', 204);
        }
    }

    public function allGoal()
    {
        $user = Auth::user();

        $goal = Goal::where('user_id', '=', $user->id)->orderby('firstDate')->get();

        if ($goal) {
            return response($goal, 200);
        } else {
            return response('', 204);
        }
    }
}
