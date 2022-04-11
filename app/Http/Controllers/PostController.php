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
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Environment\Console;

class PostController extends Controller
{
    public function image(Request $request)
    {
        $post = "1";
        return $this->saveImage($request, $post);
        if ($request->hasFile("img")) {
            for ($i = 0; $i < count($request->img); $i++) {
                $path[$i] = $request->img[$i]->store('image', 's3');
                Image::create([
                    'image' => basename($path[$i]),
                    'url' => Storage::url($path[$i]),
                    'post_id' => 1
                ]);
            }
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

        // return json_encode($gpsData);

        if ($request->track_id) {
            $gpsData["track_id"] = $request->track_id;
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
            ["mmr" => Auth::user()->mmr],
            ["date" => Carbon::now()->format('Y-m-d')],
            ["gps_id" => $gps_id],  //노드에서 받아와야할 정보
        );


        if ($request->kind == "자유") {
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
        $gpsData = array();

        //배열에 팔로잉한 아이디 push
        for ($i = 0; $i < $array_length; $i++) {
            array_push($array, $followings[$i]->following_id);
        }
        //팔로잉한 아이디의 포스트만 시간별로 출력
        $post = Post::with(['user', 'likes', 'comment'])->whereIn('user_id', $array)->where('range', 'public')->orderby('created_at', 'desc')->paginate(10);


        //gpsData를 요청해서 같이 묶어서 보내줘야함
        for ($i = 0; $i < $post->count(); $i++) {
            $gpsId = $post[$i]->gps_id;
            $response = Http::get("http://13.124.24.179/api/gpsdata/$gpsId");
            $data = json_decode($response->getBody(), true);
            array_push($gpsData, $data);
            $post[$i]["gpsData"] = $gpsData[$i];
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

    //내 활동내역 보기
    public function myIndex()
    {
        $user = Auth::user()->id;

        //최근 게시물 순으로 보여줌
        $post = Post::with(['user', 'likes', 'comment'])->orderby('created_at', 'desc')->where('user_id', '=', $user)->paginate(10);

        //gpsData를 요청해서 같이 묶어서 보내줘야함
        $gpsData = array();
        for ($i = 0; $i < $post->count(); $i++) {
            $gpsId = $post[$i]->gps_id;
            $response = Http::get("http://13.124.24.179/api/gpsdata/$gpsId");
            $data = json_decode($response->getBody(), true);
            array_push($gpsData, $data);
            $post[$i]["gpsData"] = $gpsData[$i];
        }

        $post[0]["gpsData"] = $gpsData[0];

        if ($post) {
            return response(
                $post,
                200
            );
        } else {
            return response('', 204);
        }
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
        $user = Auth::user();
        $event = $request->event;

        //주간 체크
        $today = time();
        $week = date("w");
        $week_first = $today - ($week * 86400);  //이번주의 첫째 날
        $week_last = $week_first + (6 * 86400);  //이번주의 마지막 날
        $first = date('Y-m-d', $week_first);
        $last = date('Y-m-d', $week_last);

        $post_distance = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->get('distance');
        $post_date = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->get('date');
        $count = Post::where('user_id', '=', $user->id)->where('date', '>=', $first)->where('date', '<=', $last)->where('event', '=', $event)->count();

        //요일별 저장 함수 실행
        return $this->weekData($post_distance, $post_date, $count);
    }

    public function goal(Request $request)
    {
        //날짜 입력 받기
        //그 날짜 범위의 활동만 주행거리로 누적
        //날짜 지정
        //목표테이블
        //달성하면 달성 메세지 출력
        $firstDay = $request->firstDay;
        $lastDay = $request->lastDay;
        $myGoal = $request->myGoal;

        $user = Auth::user();

        $post = Post::where('user_id', '=', $user->id)->where('date', '>=', $firstDay)->where('date', '<=', $lastDay)->get();

        $goal = 0;
        for ($i = 0; $i < count($post); $i++) {
            $goal += $post[$i]->distance;
        }
        return $goal;
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
            //무승부면 mmr +5
            DB::table('users')->where('id', $id)->increment('mmr', 5);
            return response([
                'message' => '무승부입니다'
            ], 200);
        } else {
            //지면 mmr +3
            DB::table('users')->where('id', $id)->increment('mmr', 3);
            return response([
                'message' => '패배하셨습니다'
            ], 200);
        }
    }

    protected function saveImage($request, $input)
    {
        $post = Post::create($input);
        for ($i = 0; $i < count($request->img); $i++) {
            $path[$i] = $request->img[$i]->store('image', 's3');
            Image::create([
                'image' => basename($path[$i]),
                'url' => Storage::url($path[$i]),
                'post_id' => $post->id
            ]);
        }
    }
}



// {
//     "title":"ㅎㅎ데이터",
//     "event" : "B",
//     "time" : 50,
//     "calorie" : 2500.5,
//     "average_speed" : 20,
//     "altitude" : 20,
//     "distance" : 15,
//     "img" : "img",
//     "content" : "test",
//     "range" : "public",
//     "kind" : "랭크",
//     "track_id" : "622561232d6ee07c40f75bdc",
//     "opponent_id" : 30,
//     "gpsData" : {
//         "speed": [
//             42, 40, 41, 43, 42, 43, 41, 42, 42, 43, 43, 40, 43, 43, 43, 43, 43, 43,
//             41, 43, 43, 43, 40, 42, 43, 43, 43, 41, 41, 43, 43, 43, 43, 40, 43, 42,
//             43, 41, 43, 42, 41, 43, 43, 42, 42, 43, 41, 43, 43, 43, 43, 43, 43, 42,
//             43, 43, 42, 41, 41, 43, 42, 40, 41, 43, 41
//         ],
//         "gps": [
//             [128.61303806304932, 35.89588170142396],
//             [128.61265182495117, 35.89560358073926],
//             [128.61237287521362, 35.895290693801016],
//             [128.61194372177124, 35.89487350929305],
//             [128.61162185668942, 35.89457800226974],
//             [128.61117124557495, 35.89421296265922],
//             [128.61084938049316, 35.89386530432244],
//             [128.6104416847229, 35.89344811230307],
//             [128.6100125312805, 35.8931525999592],
//             [128.60951900482178, 35.89271802097918],
//             [128.6090898513794, 35.89237035607764],
//             [128.6085319519043, 35.89210960639962],
//             [128.60801696777344, 35.891796705652425],
//             [128.60726594924927, 35.89136211922988],
//             [128.606858253479, 35.89117090044829],
//             [128.6064076423645, 35.890979681204854],
//             [128.60582828521729, 35.890736310590775],
//             [128.60514163970947, 35.89038863698731],
//             [128.6043906211853, 35.88997142664797],
//             [128.60406875610352, 35.88976282065393],
//             [128.60376834869382, 35.889189151336524],
//             [128.60376834869382, 35.888685014262876],
//             [128.60402584075928, 35.887954879017514],
//             [128.60436916351318, 35.88739858097851],
//             [128.60490560531616, 35.886651049334596],
//             [128.6056137084961, 35.88656412659245],
//             [128.6062788963318, 35.88656412659245],
//             [128.6074161529541, 35.88642505000656],
//             [128.60799551010132, 35.88626858855547],
//             [128.60836029052732, 35.885833971791584],
//             [128.6087679862976, 35.88552104624466],
//             [128.60945463180542, 35.885312428526404],
//             [128.60994815826416, 35.885833971791584],
//             [128.60996961593628, 35.886355511622014],
//             [128.6105489730835, 35.886355511622014],
//             [128.6111283302307, 35.886355511622014],
//             [128.61196517944336, 35.88632074240684],
//             [128.61286640167236, 35.886146896102005],
//             [128.61372470855713, 35.88560797013171],
//             [128.61411094665527, 35.884808266771834],
//             [128.61456155776978, 35.88508642537816],
//             [128.61499071121216, 35.885347198184284],
//             [128.6154842376709, 35.885799202347435],
//             [128.61565589904785, 35.88663366479379],
//             [128.61584901809692, 35.88715519935755],
//             [128.61606359481812, 35.8876245775282],
//             [128.61640691757202, 35.88823302657146],
//             [128.61664295196533, 35.8886154778629],
//             [128.61685752868652, 35.888928391181906],
//             [128.61625671386716, 35.889484678473636],
//             [128.6161708831787, 35.89005834564997],
//             [128.61599922180176, 35.890579857658125],
//             [128.61561298370358, 35.89104921552864],
//             [128.61546277999878, 35.891466420188856],
//             [128.61522674560547, 35.8919531561806],
//             [128.61492633819577, 35.892509422221465],
//             [128.61475467681885, 35.89278755377628],
//             [128.6145830154419, 35.8931352168458],
//             [128.6143684387207, 35.8937436235439],
//             [128.6141324043274, 35.8941086653185],
//             [128.6139178276062, 35.89436940841269],
//             [128.6136817932129, 35.89497780562615],
//             [128.61421823501587, 35.89534284170994],
//             [128.61376762390137, 35.89567311100204],
//             [128.6132526397705, 35.89588170142396]
//         ],

//         "altitude": [
//             143, 142, 141, 144, 145, 143, 142, 144, 143, 142, 142, 141, 143, 144, 141,
//             142, 141, 142, 141, 138, 137, 135, 134, 133, 132, 129, 126, 127, 128, 129,
//             124, 122, 120, 113, 115, 111, 110, 107, 102, 111, 115, 120, 122, 124, 125,
//             123, 122, 125, 128, 129, 130, 131, 132, 133, 135, 136, 138, 140, 141, 142,
//             144, 146, 143, 144, 144
//         ],
//         "distance": [
//             1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21,
//             22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39,
//             40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
//             58, 59, 60, 61, 62, 63, 64, 65
//         ],
//         "time": [
//             1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21,
//             22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39,
//             40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
//             58, 59, 60, 61, 62, 63, 64, 65
//         ]
//     }
// }
