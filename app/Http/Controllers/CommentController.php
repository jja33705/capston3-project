<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\InvoicePaid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate(['content' => ['required']]);

        $user_id = Auth::user()->id;
        $comment = Comment::create(
            [
                'content' => $request->content,
                'post_id' => $id,
                'user_id' => $user_id,
            ]
        );
        $post = Post::where('id', '=', $id)->first('user_id');

        User::find($post->user_id)->notify(new InvoicePaid("comment", $user_id, $id));


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
