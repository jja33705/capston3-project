<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dotenv\Util\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;


class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $user = Socialite::driver('google')->user();
        // dd($user);

        $user = User::firstOrCreate(
            ['email' => $user->getEmail()],
            [
                'password' => Hash::make(Str::random(24)),
                'name' => $user->getName()
            ]
        );

        return Auth::login($user);
    }
}
