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
        $this->week_record($post, $user);


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

    public function weekRecord()
    {
        $user = Auth::user();
        return response([
            '이름' => $user->name,
            '요일별 기록' => DayRecord::where('user_id', '=', $user->id)->get()
        ]);
    }

    protected function week_record($post, $user)
    {
        $today = time();
        $week = date("w");

        $week_first = $today - ($week * 86400);  //이번주의 첫째 날
        $week_last = $week_first + (6 * 86400);  //이번주의 마지막 날

        //요일별 주간 기록 저장
        $date = Post::where('user_id', $user->id)->where('id', $post->id)->get();
        //주행거리
        $distance = $date[0]->distance;
        //생성날짜
        $day = $date[0]->date;

        if ($week_first <= $day || $week_last >= $day) {
            //요일별로 저장하는 함수
            $this->save($user, $distance, $day);
        } else if ($week_first == $day) {  //그 주의 첫째날과 오늘이 같아지면 한주가 바뀐것이므로 DB를 갱신해준다
            DB::table('day_records')->where('id', $user->id)->update([
                'Sun' => 0,
                'Mon' => 0,
                'Tue' => 0,
                'Wed' => 0,
                'Tur' => 0,
                'Fri' => 0,
                'Sat' => 0
            ]);
            $this->save($user, $distance, $day);
        }
    }


    //요일별로 누적거리 저장하기
    protected function save($user, $distance, $day)
    {
        //요일별로 구분
        $array_week = array("일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일");
        //날짜보고 요일 추출
        $weekday = $array_week[date('w', strtotime($day))];

        switch ($weekday) {
            case "일요일";
                DB::table('day_records')->where('id', $user->id)->increment('Sun', $distance);
                break;
            case "월요일";
                DB::table('day_records')->where('id', $user->id)->increment('Mon', $distance);
                break;
            case "화요일";
                DB::table('day_records')->where('id', $user->id)->increment('Tue', $distance);
                break;
            case "수요일";
                DB::table('day_records')->where('id', $user->id)->increment('Wed', $distance);
                break;
            case "목요일";
                DB::table('day_records')->where('id', $user->id)->increment('Tur', $distance);
                break;
            case "금요일";
                DB::table('day_records')->where('id', $user->id)->increment('Fri', $distance);
                break;
            case "토요일";
                DB::table('day_records')->where('id', $user->id)->increment('Sat', $distance);
                break;
        };
    }
}
