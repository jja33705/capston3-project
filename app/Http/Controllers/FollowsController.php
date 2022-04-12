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
        $user2 = Auth::user();
        $user->followers()->toggle($user2->id);
        return $user->name;

        User::find($user->id)->notify(new InvoicePaid($user->name, $user2->id));
        return User::where('id', '=', $user->id)->get(['id', 'sex', 'name', 'profile', 'mmr']);
    }
}
