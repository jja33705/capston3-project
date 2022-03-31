<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TrackController extends Controller
{
    public function addTrack(Request $request)
    {
        $gpsData = $request->gpsData;
        $response = Http::post('http://13.124.24.179/api/tracks', $gpsData);
        return json_decode($response, true);
    }

    public function allTracks()
    {
        //Node에서 track_id를 리턴
        $response = Http::get('http://13.124.24.179/api/tracks');
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }

    public function search(Request $request)
    {
        $bound1 = $request->query('bound1');
        $bound2 = $request->query('bound2');
        $bound3 = $request->query('bound3');
        $bound4 = $request->query('bound4');
        $event = $request->query('event');

        //쿼리스트링을 만듦
        $query = "bounds" . '=' . $bound1  . '&' .  "bounds" . '=' . $bound2 . '&' . "bounds" . '=' . $bound3 . '&' . "bounds" . '=' . $bound4 . '&' . "event" . '=' . $event;
        //Node에서 track_id를 리턴
        $response = Http::get("http://13.124.24.179/api/tracks/search?$query");

        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }

    public function track(Request $request)
    {
        $id = $request->query('track_id');
        //Node에서 track_id를 리턴
        $response = Http::get("http://13.124.24.179/api/tracks/$id");
        //JSON 문자열을 변환하여 값을 추출
        return json_decode($response, true);
    }
}
