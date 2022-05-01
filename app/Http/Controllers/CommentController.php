<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\InvoicePaid;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate(['content' => ['required']]);

        $me = Auth::user();
        $comment = Comment::create(
            [
                'content' => $request->content,
                'post_id' => $id,
                'user_id' => $me->id,
            ]
        );
        $post = Post::where('id', '=', $id)->first();
        $user = User::find($post->user_id);


        //fcm알림설정
        FCMService::send(
            $user->fcm_token,
            [
                'title' => '알림',
                'body' => $me->name . '님이' . ' ' . '회원님의 ' . $post->title . '게시물에 댓글을 남겼습니다.'
            ],
            [
                'message' => ['허허허허허헣허']
            ],
        );
        User::find($post->user_id)->notify(new InvoicePaid("comment", $me->id, $id));

        if ($comment) {
            return response([
                'message' => ['댓글달기 성공'],
                'comment' => $comment
            ], 201);
        } else {
            return response([
                'message' => ['실패']
            ], 401);
        }
    }

    public function reply(Request $request, $id)
    {
        $reply = Reply::create(
            [
                'comment_id' => $id,
                'user_id' => Auth::user()->id,
                'content' => $request->content
            ]
        );

        if ($reply) {
            return response([
                'message' => ['대댓글달기 성공'],
                'reply' => $reply
            ], 201);
        } else {
            return response([
                'message' => ['실패']
            ], 401);
        }
    }

    public function destroy($id)
    {

        $user = Auth::user()->id;
        $comment = Comment::find($id);

        $user_id = $comment->user_id;

        if ($user == $user_id) {
            $comment->delete();
            return "댓글 삭제 성공";
        } else {
            return abort(401);
        }
    }
}
