<?php

namespace App\Http\Controllers;

use App\Models\follow;
use App\Models\User;
use App\Notifications\InvoicePaid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowsController extends Controller
{
    public function store(User $user)
    {
        //현재 로그인한 유저의 id
        $me = Auth::user();

        if ($user->id != $me->id) {
            $follow = $user->followers()->toggle($me->id);
        } else {
            return response('본인은 팔로우할 수 없습니다', 400);
        }

        if ($follow['attached']) {
            User::find($user->id)->notify(new InvoicePaid("follow", $me->id, "null"));
        };

        return response(
            User::where('id', '=', $user->id)->get(['id', 'sex', 'name', 'profile', 'mmr']),
            200
        );
    }
}
