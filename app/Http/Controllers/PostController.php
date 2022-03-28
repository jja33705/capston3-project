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
        $gps_id = $data["gpsDataId"];

        $user = Auth::user();

        $input = array_merge(
            $request->all(),
            ["user_id" => $user->id],
            ["mmr" => Auth::user()->mmr],
            ["date" => Carbon::now()->format('Y-m-d')],
            ["gps_id" => $gps_id],  //노드에서 받아와야할 정보
        );
        $post = Post::create($input);

        //요일별로 누적 거리 저장
        // $this->week_record($post, $user);

        if ($request->hasFile("image")) {
            return $request;
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
            ], 200);
        } else {
            $myTime = $request->time;
            $opponentTime = Post::where('id', '=', $request->opponent_id)->first('time');
            //시간을 비교해서 mmr을 상승
            return $this->mmrPoint($myTime, $opponentTime);
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
        $user = Auth::user()->id;

        //최근 게시물 순으로 보여줌
        return Post::orderby('created_at', 'desc')->where('user_id', '=', $user)->paginate(5);
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

        //게시물 업데이트
        if ($user == $user_id) {
            $post->content = $request->content;
            $post->range = $request->range;
            $post->save();
            return response([
                'message' => ['수정 완료']
            ], 200);
        } else {
            return abort(401);
        }
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user()->id;
        $user_id = $post->user_id;

        //게시물 삭제
        if ($user == $user_id) {
            $post->delete();
            return response([
                'message' => ['삭제 성공']
            ], 200);
        } else {
            return abort(401);
        }
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


        $post_distance = Post::where('user_id', '=', $user->id)->where('date', '>=', $first && 'date', '<=', $last)->where('event', '=', $event)->get('distance');
        $post_date = Post::where('user_id', '=', $user->id)->where('date', '>=', $first && 'date', '<=', $last)->where('event', '=', $event)->get('date');
        $count = Post::where('user_id', '=', $user->id)->where('date', '>=', $first && 'date', '<=', $last)->where('event', '=', $event)->count();

        //요일별 저장 함수 실행
        return $this->weekData($post_distance, $post_date, $count);
    }



    //날짜 데이터를 보고 요일별로 나누어 요일별 누적 거리 계산
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

        //반복문으로 요일별로 확인하여 누적 거리 저장
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

        //요일별 누적 거리
        $weekRecord = ([
            "Mon" => $Mon,
            "Tue" => $Tue,
            "Wed" => $Wed,
            "Tur" => $Tur,
            "Fri" => $Fri,
            "Sat" => $Sat,
            "Sun" => $Sun
        ]);

        if ($weekRecord) {
            return response($weekRecord, 200);
        } else {
            return response([
                "Mon" => 0,
                "Tue" => 0,
                "Wed" => 0,
                "Tur" => 0,
                "Fri" => 0,
                "Sat" => 0,
                "Sun" => 0,
                200
            ]);
        }
    }


    //mmr상승 함수
    protected function mmrPoint($myTime, $opponentTime)
    {
        $id = Auth::user()->id;
        //이기면 mmr +10
        if ($myTime < $opponentTime->time) {
            DB::table('users')->where('id', $id)->increment('mmr', 10);
            return response([
                'message' => '승리하셨습니다'
            ], 200);
        } else if ($myTime == $opponentTime->time) {
            //무승부면 mmr + 3
            DB::table('users')->where('id', $id)->increment('mmr', 3);
            return response([
                'message' => '무승부 입니다'
            ], 200);
        } else {
            //지면 mmr +1
            DB::table('users')->where('id', $id)->increment('mmr', 1);
            return response([
                'message' => '패배하셨습니다'
            ], 200);
        }
    }
}
