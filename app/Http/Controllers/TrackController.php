<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TrackController extends Controller
{
    public function addTrack(Request $request)
    {
        $response = Http::post('http://13.124.24.179/api/tracks', $request);
        return json_decode($response, true);
    }

    public function allTracks()
    {
        //Node에서 track_id를 리턴
        $response = Http::get('http://13.124.24.179/api/tracks');
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }

    public function search($id)
    {
        //Node에서 track_id를 리턴
        $response = Http::get("http://13.124.24.179/api/tracks/search/$id");
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }

    public function track($id)
    {
        //Node에서 track_id를 리턴
        $response = Http::get("http://13.124.24.179/api/tracks/$id");
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }

    public function rank($id)
    {
        //Node에서 track_id를 리턴
        $response = Http::get("http://13.124.24.179/api/tracks/$id/ranks");
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }
}
