<?php

namespace App\Http\Controllers;

use App\Models\DayRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function test()
    {
        return "test";
    }

    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'sex' => $request->input('sex'),
            'weight' => $request->input('weight'),
            'profile' => $request->input('profile'),
            'birth' => $request->input('birth'),
            'introduce' => $request->input('introduce'),
            'location' => $request->input('location'),
            'mmr' => 0,
        ]);

        //요일별 누적 거리
        DayRecord::create([
            'user_id' => $user->id,
            'Mon' => 0,
            'Tue' => 0,
            'Wed' => 0,
            'Tur' => 0,
            'Fri' => 0,
            'Sat' => 0,
            'Sun' => 0,
        ]);

        return response([
            'message' => '회원가입 성공',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response([
                'message' => 'Invalid credentials!'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $login_user = Auth::user();

        $login_token = $login_user->createToken('token')->plainTextToken;

        $cookie = cookie('login_token', $login_token, 60 * 24); // 1 day


        $user = User::with(['followings', 'followers', 'posts'])->find($login_user->id);

        return response([
            'access_token' => $login_token,
            'user' => $user,
        ])->withCookie($cookie);
    }

    public function user()
    {
        $user_id = Auth::user()->id;
        return User::with(['followings', 'followers', 'posts'])->find($user_id);
    }

    public function logout()
    {
        $user = Auth::user();
        $user->tokens()->delete();

        $cookie = Cookie::forget('login_token');
        return response([
            'message' => 'Success'
        ], 201)->withCookie($cookie);
    }
}
