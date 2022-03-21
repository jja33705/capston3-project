<?php

namespace App\Http\Controllers;

use App\Models\follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowsController extends Controller
{
    public function store(User $user)
    {
        //현재 로그인한 유저의 id
        $user_id = Auth::user()->getAttribute('id');
        $user->followers()->toggle($user_id);
        return User::where('id', '=', $user->id)->get(['id', 'sex', 'name', 'profile', 'mmr']);
    }
}
