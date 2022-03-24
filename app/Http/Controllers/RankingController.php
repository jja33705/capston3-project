<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RankingController extends Controller
{
    public function track($id)
    {
        $response = Http::get('http://13.124.24.179/api/track/622561232d6ee07c40f75bdc/rank');
        return $response;
    }

    public function mmr()
    {
        return response(
            User::orderby('mmr', 'desc')->get('id'),
            200
        );
    }
}
