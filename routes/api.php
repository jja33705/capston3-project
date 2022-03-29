<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowsController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MMRController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\TrackController;
use App\Models\Comment;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




//로그인, 회원가입
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test', [AuthController::class, 'test']);

Route::get('/auth/login/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/login/google/callback', [GoogleAuthController::class, 'callback']);


//현재로그인 확인
Route::middleware('auth:sanctum')->group(function () {
    //유저확인, 로그아웃
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('userSearch', [AuthController::class, 'userSearch']);

    // 운동sns
    Route::prefix('post')->group(function () {
        Route::post('/store', [PostController::class, 'store']);
        Route::get('/index', [PostController::class, 'index']);
        Route::get('/myIndex', [PostController::class, 'myIndex']);
        Route::get('/show/{id}', [PostController::class, 'show']);
        Route::put('/update/{id}', [PostController::class, "update"]);
        Route::delete('/{id}', [PostController::class, "destroy"]);
        //일주일 간격으로 요일별 누적 거리 구하는 라우터
        Route::get('/weekRecord', [PostController::class, "weekRecord"]);
    });

    // 팔로우
    Route::post('/follow/{user}', [FollowsController::class, 'store']);

    //게시글 좋아요
    Route::post('/like/{post}', [LikeController::class, 'store']);

    //댓글
    Route::prefix('comment')->group(function () {
        Route::post('/store/{id}', [CommentController::class, 'store']);
        Route::delete('/destroy/{id}', [CommentController::class, 'destroy']);
        Route::post('/store/reply/{id}', [CommentController::class, 'reply']);
    });

    //mmr
    Route::prefix('/match')->group(function () {
        Route::post('/rank', [MMRController::class, 'rank']);
        Route::post('/friendly', [MMRController::class, 'friendly']);
        Route::post('/gpsData', [MMRController::class, 'gpsData']);
    });


    //누적 기록 불러오기
    Route::prefix('/record')->group(function () {
        Route::get('/type', [RecordController::class, 'type']);  //자전거 달리기 비율
        Route::get('/totalTime', [RecordController::class, 'totalTime']);  //누적 시간
        Route::get('/totalCalorie', [RecordController::class, 'totalCalorie']);  //누적 칼로리
    });

    //랭킹조회
    Route::prefix('/ranking')->group(function () {
        Route::get('/mmr', [RankingController::class, 'mmr']);
        Route::get('/track/{id}', [RankingController::class, 'track']);
    });

    Route::prefix('/tracks')->group(function () {
        Route::post('/', [TrackController::class, 'addTrack']);  //트랙 만들기
        Route::get('/', [TrackController::class, 'allTracks']);  //모든 트랙 id리턴
        Route::get('/search', [TrackController::class, 'search']);  //구간에 맞는 트랙 리턴
        Route::get('/{id}', [TrackController::class, 'track']);  //트랙아이디로 트랙 리턴
        Route::get('/rank/{id}', [TrackController::class, 'rank']);  //선택한 트랙의 GPSdata를 시간 순으로 정렬하고 트랙도 같이 리턴
    });
});
