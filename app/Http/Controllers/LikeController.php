<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Notifications\InvoicePaid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Post $post)
    {
        //토글을 이용해서 좋아요 설정
        $me = Auth::user();
        $like = $post->likes()->toggle($me->id);

        if ($like['attached']) {
            User::find($post->user_id)->notify(new InvoicePaid("like", $me->id, $post->id));
        }

        return $like;
    }
}
