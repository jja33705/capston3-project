<?php

namespace App\Http\Controllers;

use App\Models\DayRecord;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\Image;
use App\Models\MapImage;
use App\Models\Post;
use App\Models\Record;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use phpDocumentor\Reflection\Types\Boolean;
use SebastianBergmann\Environment\Console;
use App\Services\FCMService;

class PostController extends Controller
{
    public function profile($id)
    {
        $me = Auth::user();
        $user = User::find($id);

        $followings = Follow::where('follower_id', '=', $me->id)->get('following_id');

        $follow = array();
        for ($i = 0; $i < count($followings); $i++) {
            array_push($follow, $followings[$i]['following_id']);
        }


        if ($user) {
            if (in_array($user->id, $follow)) {
                $profile = User::with(['posts'])->where('id', '=', $id)->first();
                return response($profile, 200);
            } else if ($user->id == $me->id) {
                $profile = User::with(['posts'])->where('id', '=', $id)->first();
                return response($profile, 200);
            } else {
                return response($user, 200);
            }
        } else {
            return response('', 204);
        }
    }

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
        $gpsData = json_decode($request->gpsData, true);
        $gpsData["userId"] = $user->id;
        $gpsData["name"] = $user->name;
        $gpsData["event"] = $request->event;
        $gpsData["totalTime"] = $request->time;


        if ($request->track_id) {
            $gpsData["trackId"] = $request->track_id;
        }

        //Node에서 GPS_data_id를 받아와서 활동에 저장
        $response = Http::post('http://13.124.24.179/api/gpsdata', $gpsData);


        //JSON 문자열을 변환하여 값을 추출
        $data = json_decode($response, true);
        $gps_id = $data["gpsDataId"];

        $user = Auth::user();


        $input = array_merge(
            ["title" => $request->title],
            ["event" => $request->event],
            ["time" => $request->time],
            ["calorie" => $request->calorie],
            ["average_speed" => $request->average_speed],
            ["altitude" => $request->altitude],
            ["distance" => $request->distance],
            ["content" => $request->content],
            ["range" => $request->range],
            ["kind" => $request->kind],
            ["track_id" => $request->track_id],
            ["opponent_id" => $request->opponent_id],
            ["user_id" => $user->id],
            ["date" => Carbon::now()->format('Y-m-d')],
            ["gps_id" => $gps_id],  //노드에서 받아와야할 정보
        );

        if ($request->event == 'B') {
            $input['mmr'] = Auth::user()->mmr;
        } else {
            $input['mmr'] = Auth::user()->run_mmr;
        }

        //맵이미지 유무 확인하고 저장
        if ($request->hasFile('mapImg')) {
            for ($i = 0; $i < count($request->mapImg); $i++) {
                $path[$i] = $request->mapImg[$i]->store('mapImage', 's3');
                $input["img"] = Storage::url($path[$i]);
            }
        }

        if ($request->kind == "자유") {
            //이미지 유무 확인후 있으면 save메서드 호출
            if ($request->hasFile('img')) {
                $this->saveImage($request, $input);
            } else {
                Post::create($input);
            }
            return response([
                'message' => '자유 기록을 저장했습니다'
            ], 201);
        } else if ($request->kind == "싱글") {
            if ($request->hasFile('img')) {
                $this->saveImage($request, $input);
            } else {
                Post::create($input);
            }
            return response([
                'message' => '싱글전 기록을 저장 했습니다.'
            ], 201);
        } else if ($request->kind == "친선") {
            if ($request->hasFile('img')) {
                $this->saveImage($request, $input);
            } else {
                Post::create($input);
            }
            return response([
                'message' => '친선전 기록을 저장 했습니다.'
            ], 201);
        } else {
            $myTime = $request->time;
            $opponentTime = Post::where('id', '=', $request->opponent_id)->first('time');
            if ($opponentTime) {
                if ($request->hasFile('img')) {
                    $this->saveImage($request, $input);
                } else {
                    Post::create($input);
                }
            }
            //시간을 비교해서 mmr을 상승
            $type = $input['event'];
            return $this->mmrPoint($myTime, $opponentTime, $type);
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

        array_push($array, $id);

        //팔로잉한 아이디의 포스트만 시간별로 출력
        $post = Post::with(['user', 'likes', 'image'])->whereIn('user_id', $array)->where('range', 'public')->orderby('created_at', 'desc')->paginate(10);


        $opponent_post = array();
        $opponent_user = array();
        $array = array();
        $array2 = array();


        for ($i = 0; $i < count($post); $i++) {
            if ($post[$i]->opponent_id) {
                $op_post = Post::where('id', '=', $post[$i]->opponent_id)->first();
                $op_user = User::where('id', '=', $op_post->user_id)->first();
                array_push($opponent_post, $op_post);
                array_push($opponent_user, $op_user);
                $post[$i]["opponent_post"] = $opponent_post[$i];
                $post[$i]['opponent_post']['user'] = $opponent_user[$i];
            }
            if (count($post[$i]->likes) !== 0) {
                for ($y = 0; $y < count($post[$i]->likes); $y++) {
                    array_push($array, $post[$i]->likes[$y]['id']);
                }
                array_push($array2, $array);
            } else {
                array_push($array2, []);
            }
            $post[$i]['likeCheck'] = in_array($id, $array2[$i]);
        }

<<<<<<< HEAD
        // fcmToken exemple
        FCMService::send(
            Auth::user()->fcm_token,
            [
                'title' => '이게 뭐노',
                'body' => '이게 가나?',
            ],
            [
                'message1' => '허허허허허헣허',
                "message2" => '후훟훟후후후훟후'
            ],
        );
=======
>>>>>>> 92eedf46047c6d2b3977fad82b3e05bb44175aed

        //gpsData를 요청해서 같이 묶어서 보내줘야함
        // for ($i = 0; $i < $post->count(); $i++) {
        //     $gpsId = $post[$i]->gps_id;
        //     $response = Http::get("http://13.124.24.179/api/gpsdata/$gpsId");
        //     $data = json_decode($response->getBody(), true);
        //     array_push($gpsData, $data);
        //     $post[$i]["gpsData"] = $gpsData[$i];
        // }
        if ($post) {
            return response(
                $post,
                200
            );
        } else {
            return response('', 204);
        }
    }

    //내 활동내역 보기
    public function myIndex()
    {
        $user = Auth::user()->id;

        //최근 게시물 순으로 보여줌
        $post = Post::with(['user', 'likes', 'comment', 'image', 'mapImage'])->orderby('created_at', 'desc')->where('user_id', '=', $user)->paginate(10);


        $opponent_post = array();
        for ($i = 0; $i < count($post); $i++) {
            if ($post[$i]->opponent_id) {
                $op_post = Post::where('id', '=', $post[$i]->opponent_id)->first();
                array_push($opponent_post, $op_post);
                $post[$i]["opponent_post"] = $opponent_post[$i];
            } else {
                return response(
                    $post,
                    200
                );
            }
        }


        if ($post) {
            return response(
                $post,
                200
            );
        } else {
            return response('', 204);
        }
    }

    public function show($id)
    {
        $post = Post::with('user', 'likes', 'image')->where('id', '=', $id)->first();
        return response($post, 200);
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
            $post->title = $request->title;
            $post->content = $request->content;
            $post->range = $request->range;
            $post->save();
            return $post->id;
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
            // if ($post->img) {
            //     Storage::disk('s3')->delete('https://run-images.s3.ap-northeast-2.amazonaws.com/mapImage/LoS68WJHuvEu5tjPPM9R0aLqnjw4baLD52YHiDkv.jpg');
            // };
            Storage::disk('s3')->deleteDirectory('https://run-images.s3.ap-northeast-2.amazonaws.com/mapImage/SeLYgWx2t5NFaC3L3mSMJYrb9yzrSyYPcMoggy0f.jpg');

            $post->delete();
            return $post->id;
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
        $user = Auth::user()->id;
        $event = $request->query('event');

        //주간 체크
        $today = time();
        $week = date("w");
        $week_first = $today - ($week * 86400);  //이번주의 첫째 날
        $week_last = $week_first + (6 * 86400);  //이번주의 마지막 날
        $first = date('Y-m-d', $week_first);
        $last = date('Y-m-d', $week_last);

        $day = time();
        $one = $today - 86400;
        $two = $one - 86400;
        $three = $two - 86400;
        $four = $three - 86400;
        $five = $four - 86400;
        $six = $five - 86400;

        $day2 = date('Y-m-d', time());
        $one2 = date('Y-m-d', $today - 86400);
        $two2 = date('Y-m-d', $one - 86400);
        $three2 = date('Y-m-d', $two - 86400);
        $four2 = date('Y-m-d', $three - 86400);
        $five2 = date('Y-m-d', $four - 86400);
        $six2 = date('Y-m-d', $five - 86400);


        $today = 0;
        $oneDayAgo = 0;
        $twoDayAgo = 0;
        $threeDayAgo = 0;
        $fourDayAgo = 0;
        $fiveDayAgo = 0;
        $sixDayAgo = 0;

        $day3 = Post::where('user_id', '=', $user)->where('date', '=', $day2)->where('event', '=', $event)->get('distance');

        $one3 = Post::where('user_id', '=', $user)->where('date', '=', $one2)->where('event', '=', $event)->get('distance');

        $two3 = Post::where('user_id', '=', $user)->where('date', '=', $two2)->where('event', '=', $event)->get('distance');

        $three3 = Post::where('user_id', '=', $user)->where('date', '=', $three2)->where('event', '=', $event)->get('distance');

        $four3 = Post::where('user_id', '=', $user)->where('date', '=', $four2)->where('event', '=', $event)->get('distance');

        $five3 = Post::where('user_id', '=', $user)->where('date', '=', $five2)->where('event', '=', $event)->get('distance');

        $six3 = Post::where('user_id', '=', $user)->where('date', '=', $six2)->where('event', '=', $event)->get('distance');


        for ($i = 0; $i < count($day3); $i++) {
            $today += $day3[$i]->distance;
        }
        for ($i = 0; $i < count($one3); $i++) {
            $oneDayAgo += $one3[$i]->distance;
        }
        for ($i = 0; $i < count($two3); $i++) {
            $twoDayAgo += $two3[$i]->distance;
        }
        for ($i = 0; $i < count($three3); $i++) {
            $threeDayAgo += $three3[$i]->distance;
        }
        for ($i = 0; $i < count($four3); $i++) {
            $fourDayAgo += $four3[$i]->distance;
        }
        for ($i = 0; $i < count($five3); $i++) {
            $fiveDayAgo += $five3[$i]->distance;
        }
        for ($i = 0; $i < count($six3); $i++) {
            $sixDayAgo += $six3[$i]->distance;
        }

        $weekRecord = ([
            "today" => $today,
            "oneDayAgo" => $oneDayAgo,
            "twoDayAgo" => $twoDayAgo,
            "threeDayAgo" => $threeDayAgo,
            "fourDayAgo" => $fourDayAgo,
            "fiveDayAgo" => $fiveDayAgo,
            "sixDayAgo" => $sixDayAgo
        ]);

        return response($weekRecord, 200);


        // $post_distance = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->get('distance');
        // $post_date = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->get('date');
        // $count = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->count();

        // //요일별 저장 함수 실행
        // return $this->weekData($post_distance, $post_date, $count);
    }

    // //날짜 데이터를 보고 요일별로 나누어 요일별 누적 거리 계산
    // protected function weekData($post_distance, $post_date, $count)
    // {
    //     $array_week = array("일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일");

    //     $Mon = 0;
    //     $Tue = 0;
    //     $Wed = 0;
    //     $Tur = 0;
    //     $Fri = 0;
    //     $Sat = 0;
    //     $Sun = 0;

    //     //반복문으로 요일별로 확인하여 누적 거리 저장
    //     for ($i = 0; $i < $count; $i++) {
    //         $day = $post_date[$i]->date;
    //         $weekday = $array_week[date('w', strtotime($day))];
    //         if ($weekday == "월요일") {
    //             $Mon += $post_distance[$i]->distance;
    //         } else if ($weekday == "화요일") {
    //             $Tue += $post_distance[$i]->distance;
    //         } else if ($weekday == "수요일") {
    //             $Wed += $post_distance[$i]->distance;
    //         } else if ($weekday == "목요일") {
    //             $Tur += $post_distance[$i]->distance;
    //         } else if ($weekday == "금요일") {
    //             $Fri += $post_distance[$i]->distance;
    //         } else if ($weekday == "토요일") {
    //             $Sat += $post_distance[$i]->distance;
    //         } else if ($weekday == "일요일") {
    //             $Sun += $post_distance[$i]->distance;
    //         }
    //     }

    //     //요일별 누적 거리
    //     $weekRecord = ([
    //         "Mon" => $Mon,
    //         "Tue" => $Tue,
    //         "Wed" => $Wed,
    //         "Tur" => $Tur,
    //         "Fri" => $Fri,
    //         "Sat" => $Sat,
    //         "Sun" => $Sun
    //     ]);

    //     if ($weekRecord) {
    //         return response($weekRecord, 200);
    //     } else {
    //         return response([
    //             "Mon" => 0,
    //             "Tue" => 0,
    //             "Wed" => 0,
    //             "Tur" => 0,
    //             "Fri" => 0,
    //             "Sat" => 0,
    //             "Sun" => 0,
    //             200
    //         ]);
    //     }
    // }


    //mmr상승 함수
    protected function mmrPoint($myTime, $opponentTime, $type)
    {
        $id = Auth::user()->id;
        //이기면 mmr +10
        if ($myTime < $opponentTime->time) {
            if ($type == 'B') {
                DB::table('users')->where('id', $id)->increment('mmr', 10);
                return response([
                    'message' => '승리하셨습니다'
                ], 200);
            } else {
                DB::table('users')->where('id', $id)->increment('run_mmr', 10);
                return response([
                    'message' => '승리하셨습니다'
                ], 200);
            }
        } else if ($myTime == $opponentTime->time) {
            //무승부면 mmr +5
            if ($type == 'B') {
                DB::table('users')->where('id', $id)->increment('mmr', 10);
                return response([
                    'message' => '무승부입니다'
                ], 200);
            } else {
                DB::table('users')->where('id', $id)->increment('run_mmr', 10);
                return response([
                    'message' => '무승부입니다'
                ], 200);
            }
        } else {
            //지면 mmr +3
            if ($type == 'B') {
                DB::table('users')->where('id', $id)->increment('mmr', 10);
                return response([
                    'message' => '패배하셨습니다'
                ], 200);
            } else {
                DB::table('users')->where('id', $id)->increment('run_mmr', 10);
                return response([
                    'message' => '패배하셨습니다'
                ], 200);
            }
        }
    }

    protected function saveImage($request, $input)
    {
        $post = Post::create($input);

        if ($request->hasFile('img')) {
            for ($i = 0; $i < count($request->img); $i++) {
                $path[$i] = $request->img[$i]->store('image', 's3');
                Image::create([
                    'image' => basename($path[$i]) . time(),
                    'url' => Storage::url($path[$i]),
                    'post_id' => $post->id
                ]);
            }
        }
    }
}
