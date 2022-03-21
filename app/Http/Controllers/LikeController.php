<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Post $post)
    {
        //토글을 이용해서 좋아요 설정
        $user_id = Auth::user()->getAttribute('id');
        return $post->likes()->toggle($user_id);
    }
}
