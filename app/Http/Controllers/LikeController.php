<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Notifications\InvoicePaid;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Post $post)
    {
        //나
        $me = Auth::user();
        //상대방
        $user = User::find($post->user_id);
        $like = $post->likes()->toggle($me->id);


        if ($like['attached']) {
            if ($like['attached'] == [$me->id]) {
                FCMService::send(
                    $user->fcm_token,
                    [
                        'title' => '알림',
                        'body' => $me->name . '님' . ' ' . '회원님의 ' . $post->title . ' 게시물을 좋아합니다'
                    ],
                    [
                        'message' => ['허허허허허헣허']
                    ],
                );
            }
        }
        // return $like['attached'] !== [$me->id];

        if ($like['attached']) {
            if ($like['attached'] == [$me->id]) {
                User::find($post->user_id)->notify(new InvoicePaid("like", $me->id, $post->id));
            }
        }

        return $like;
    }
}
