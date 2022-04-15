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
        $event = $request->event;

        $user = Auth::user();
        //목표 생성
        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => $title,
            'goalDistance' => $goalDistance,
            'firstDate' => $firstDate,
            'lastDate' => $lastDate,
            'success' => false,
            'event' => $event
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

        //해당 기간내 자전거 목표
        $goal = Goal::where('user_id', '=', $user->id)->where('event', '=', 'B')->where('firstDate', '<=', $today)->where('lastDate', '>=', $today)->get();
        //해당 기간내 달리기 목표
        // $run_goal = Goal::where('user_id', '=', $user->id)->where('event', '=', 'R')->where('firstDate', '<=', $today)->where('lastDate', '>=', $today)->get();

        //설정 목표 기간내 해당하는 자전거활동
        $array = array();
        for ($i = 0; $i < count($goal); $i++) {
            $post = Post::where('user_id', '=', $user->id)->where('event', '=', 'B')->where('date', '>=', $goal[$i]['firstDate'])->where('date', '<=', $goal[$i]['lastDate'])->orderby('date')->get();
            array_push($array, $post);
        }

        //설정 목표 기간내 해당하는 달리기활동
        // for ($i = 0; $i < count($goal); $i++) {
        //     $post = Post::where('user_id', '=', $user->id)->where('event', '=', 'R')->where('date', '>=', $goal[$i]['firstDate'])->where('date', '<=', $goal[$i]['lastDate'])->orderby('date')->get();
        //     array_push($run_array, $post);
        // }

        $data = array();
        $run_data = array();

        //0으로 설정
        for ($i = 0; $i < count($array); $i++) {
            $data[$i]['distance'] = 0;
        }
        for ($i = 0; $i < count($array); $i++) {
            for ($y = 0; $y < count($array[$i]); $y++) {
                $data[$i]['distance'] += $array[$i][$y]['distance'];
            }
            $data[$i]['goal'] = $goal[$i];
        }

        //목표 성공여부 체크
        for ($i = 0; $i < count($goal); $i++) {
            if ($data[$i]['distance'] >= $goal[$i]['goalDistance']) {
                Goal::where('id', '=', $goal[$i]['id'])->update(['success' => true]);
                // $data[$i]['message'] = $data[$i]['goal']->title . " 달성!";
                return response([
                    $data,
                ], 200);
            }
        }

        if ($data) {
            return response($data, 200);
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
