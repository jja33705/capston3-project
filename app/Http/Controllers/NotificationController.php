<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

class NotificationController extends Controller
{
    //안읽은 알림들
    public function unReadNotification()
    {
        return 1;
        $data = auth()->user()->unReadNotifications;

        return $this->notification($data);
    }

    //읽은 알림들
    public function ReadNotification()
    {
        $data = auth()->user()->ReadNotifications;

        return $this->notification($data);
    }

    //읽기
    public function read()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    //알림지우기
    public function delete()
    {
        $user = auth()->user();
        $user->notifications()->delete();
    }


    protected function notification($data)
    {
        $notifications = array();
        $user = array();

        for ($i = 0; $i < count($data); $i++) {
            array_push($user, User::where('id', '=', $data[$i]->data['user_id'])->first('name'));
            if ($data[$i]->data['type'] == 'follow') {
                $notifications[$i]['content'] = $user[$i]->name . '님이' . ' ' . '회원님을 팔로우 합니다';
                // $notifications[$i]['id'] = $data[$i]->id;
            } else if ($data[$i]->data['type'] == 'like') {
                $post = Post::where('id', '=', $data[$i]->data['post_id'])->first('title');
                $notifications[$i]['content'] = $user[$i]->name . '님이' . ' ' . '회원님의 ' . $post->title . ' 게시물을 좋아합니다';
                // $notifications[$i]['id'] = $data[$i]->id;
            } else {
                $post = Post::where('id', '=', $data[$i]->data['post_id'])->first('title');
                $notifications[$i]['content'] = $user[$i]->name . '님이' . ' ' . '회원님의 ' . $post->title . ' 게시물에 댓글을 남겼습니다.';
                // $notifications[$i]['id'] = $data[$i]->id;
            }
        };

        if ($notifications) {
            return response($notifications, 200);
        } else {
            return response('', 204);
        }
    }
}
