<?php

namespace App\Http\Controllers;

use App\Models\DayRecord;
use App\Models\Follow;
use App\Models\Image;
use App\Models\Post;
use App\Models\Record;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SebastianBergmann\Environment\Console;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'event' => 'required',
                'time' => 'required',
                'calorie' => 'required',
                'average_speed' => 'required',
                'altitude' => 'required',
                'distance' => 'required',
                'range' => 'required',
                'kind' => 'required'
            ]
        );

        $user = Auth::user();
        $gpsData = $request->gpsData;
        $gpsData["userId"] = $user->id;
        $gpsData["name"] = $user->name;
        $gpsData["event"] = $request->event;
        $gpsData["totalTime"] = $request->time;

        //Node에서 GPS_data_id를 받아와서 활동에 저장
        $response = Http::post('http://13.124.24.179/api/gpsdata', $gpsData);
        //JSON 문자열을 변환하여 값을 추출
        $data = json_decode($response, true);
        $gps_id = $data["gpsId"];

        $input = array_merge(
            $request->all(),
            ["user_id" => Auth::user()->id],
            ["mmr" => Auth::user()->mmr],
            ["date" => Carbon::now()->format('Y-m-d')],
            ["gps_id" => $gps_id],  //노드에서 받아와야할 정보
        );
        $post = Post::create($input);

        //요일별로 누적 거리 저장
        // $this->week_record($post, $user);


        if ($request->hasFile("image")) {
            $files = $request->file("images");
            foreach ($files as $file) {
                $imageName = time() . '_' . $file->getClientOriginalName();
                $request['post_id'] = $post->id;
                $request['image'] = $imageName;
                $file->move(\public_path("/images"), $imageName);
                Image::create($request->all());
            }
        }

        if ($request->kind == "혼자하기") {
            return response([
                'message' => ['혼자하기 기록을 저장했습니다']
            ], 201);
        } else {
            return redirect()->route('record.store', [
                'post_id' => $post->id,
                'win_user_id' => $request->win_user_id,
                'loss_user_id' => $request->loss_user_id,
                'kind' => $request->kind
            ]);
        }
    }

    //팔로우한 사람들 활동 내역 시간별로 보기
    public function index()
    {
        //팔로워들 id가져오기
        //where절 걸어서 팔로워들의 id의 post만 가져오기
        $id = Auth::user()->id;
        $followings = Follow::where('follower_id', '=', $id)->get('following_id');
        $array_length = count($followings);
        $array = array();

        //배열에 팔로잉한 아이디 push
        for ($i = 0; $i < $array_length; $i++) {
            array_push($array, $followings[$i]->following_id);
        }

        //팔로잉한 아이디의 포스트만 시간별로 출력
        return Post::with(['user', 'likes', 'comment'])->whereIn('user_id', $array)->where('range', 'public')->orderby('created_at', 'desc')->paginate(5);
    }

    //내 활동내역 보기
    public function myIndex()
    {
        // $range = $request->range;
        $user = Auth::user()->id;
        // if ($range == 'private') {
        //     return Post::orderby('created_at', 'desc')->where('user_id', '=', $user)->where('range', '=', 'private')->paginate(6);
        // } else if ($range == 'public') {
        //     return Post::orderby('created_at', 'desc')->where('user_id', '=', $user)->where('range', '=', 'public')->paginate(6);
        // } else {  // All일 경우 다 보여주기
        return Post::orderby('created_at', 'desc')->where('user_id', '=', $user)->paginate(5);
        // }
    }


    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'content' => 'required',
                'range' => 'required',
            ]
        );

        $post = Post::find($id);
        $user = Auth::user()->id;
        $user_id = $post->user_id;

        if ($user == $user_id) {
            $post->content = $request->content;
            $post->range = $request->range;
            $post->save();
            return response([
                'message' => ['수정 완료']
            ], 201);
        } else {
            return abort(401);
        }
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user()->id;
        $user_id = $post->user_id;

        if ($user == $user_id) {
            $post->delete();
            return response([
                'message' => ['삭제 성공']
            ], 201);
        } else {
            return abort(401);
        }
    }

    //요일별 달린 거리
    public function weekDistance()
    {
        $user = Auth::user();
        return response([
            '이름' => $user->name,
            '요일별 기록' => DayRecord::where('user_id', '=', $user->id)->get()
        ]);
    }

    public function weekRecord(Request $request)
    {
        /*자전거일 경우
         우선 유저의 모든 자전거 활동을 가져온다
         한주의 기록인지 아닌지 대소 비교
         그것을 요일별로 나눈다
        */
        $user = Auth::user();
        $event = $request->event;

        //주간 체크
        $today = time();
        $week = date("w");
        $week_first = $today - ($week * 86400);  //이번주의 첫째 날
        $week_last = $week_first + (6 * 86400);  //이번주의 마지막 날
        $first = date('Y-m-d', $week_first);
        $last = date('Y-m-d', $week_last);


        $post_distance = Post::where('user_id', '=', $user->id)->where('event', '=', $event)->where('date', '>=', $first && 'date', '<=', $last)->get('distance');
        $post_date = Post::where('user_id', '=', $user->id)->where('event', '=', $event)->where('date', '>=', $first && 'date', '<=', $last)->get('date');
        $count = Post::where('user_id', '=', $user->id)->where('event', '=', $event)->where('date', '>=', $first && 'date', '<=', $last)->count();

        return $this->weekData($post_distance, $post_date, $count);
    }



    protected function weekData($post_distance, $post_date, $count)
    {
        $array_week = array("일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일");

        $Mon = 0;
        $Tue = 0;
        $Wed = 0;
        $Tur = 0;
        $Fri = 0;
        $Sat = 0;
        $Sun = 0;

        for ($i = 0; $i < $count; $i++) {
            $day = $post_date[$i]->date;
            $weekday = $array_week[date('w', strtotime($day))];
            if ($weekday == "월요일") {
                $Mon += $post_distance[$i]->distance;
            } else if ($weekday == "화요일") {
                $Tue += $post_distance[$i]->distance;
            } else if ($weekday == "수요일") {
                $Wed += $post_distance[$i]->distance;
            } else if ($weekday == "목요일") {
                $Tur += $post_distance[$i]->distance;
            } else if ($weekday == "금요일") {
                $Fri += $post_distance[$i]->distance;
            } else if ($weekday == "토요일") {
                $Sat += $post_distance[$i]->distance;
            } else if ($weekday == "일요일") {
                $Sun += $post_distance[$i]->distance;
            }
        }
        return response([
            "Mon" => $Mon,
            "Tue" => $Tue,
            "Wed" => $Wed,
            "Tur" => $Tur,
            "Fri" => $Fri,
            "Sat" => $Sat,
            "Sun" => $Sun
        ], 201);
    }
}
