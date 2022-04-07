<?php

namespace App\Http\Controllers;

use App\Models\DayRecord;
use App\Models\Image;
use App\Models\RunRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        ], 200)->withCookie($cookie);
    }

    public function userSearch(Request $request)
    {
        $keyword = $request->keyword;

        $user = User::where('name', 'like', '%' . $keyword . '%')->get();
        return response(
            $user,
            200
        );
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        // $user = User::find($user_id);

        // $user->name = $request->name;
        // $user->weight = $request->weight;
        // $user->birth = $request->birth;
        // $user->introduce = $request->introduce;
        // $user->location = $request->location;

        if ($request->hasFile("profile")) {
            for ($i = 0; $i < count($request->profile); $i++) {
                $path[$i] = $request->profile[$i]->store('profile', 's3');
                $user->profile = Storage::url($path[$i]);
            }
        };

        $user->save();

        return $user;
        // 이제 Read/Update/Delete를 할 수 있게 하면된다.
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
            return response([
                'message' => ['수정 완료']
            ], 200);
        } else {
            return abort(401);
        }
    }
}
