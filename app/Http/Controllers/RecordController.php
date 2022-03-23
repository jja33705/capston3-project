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
    //개개인별 기록 저장
    public function store(Request $request)
    {
        //mmr상승 함수
        if ($request->kind == "랭크전") {
            $this->mmr_point($request);
        }

        $input = array_merge(
            $request->all(),
            ["user_id" => Auth::user()->id],
        );

        //기록 등록
        if (Record::create($input)) {
            return response([
                'message' => ['기록이 저장됐습니다']
            ], 201);
        } else {
            return response([
                'message' => ['실패했습니다']
            ], 405);
        }
    }

    //내 기록 불러오기
    public function myIndex()
    {
        $id = Auth::user()->getAttribute('id');
        return Record::with(['post'])->orderby('created_at', 'desc')->where('user_id', '=', $id)->paginate(5);
    }

    //상대 기록 불러오기
    public function index($id)
    {
        return Record::with(['post'])->orderby('created_at', 'desc')->where('user_id', '=', $id)->paginate(5);
    }


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
            ], 201);
        } else {
            return response([
                'message' => '활동내역이 없습니다'
            ]);
        }
    }


    public function totalTime()
    {
        $user = Auth::user();
        return Post::where('user_id', '=', $user->id)->get('time');
    }



    //mmr상승 함수
    protected function mmr_point($request)
    {
        $win_user_id = $request->win_user_id;
        // $lose_user_id = $request->lose_user_id;
        $id = Auth::user()->id;

        //이기면 mmr +10
        if ($id == $win_user_id) {
            DB::table('users')->where('id', $id)->increment('mmr', 10);
        } else {
            //지면 mmr +3
            DB::table('users')->where('id', $id)->increment('mmr', 3);
        }
    }
}
