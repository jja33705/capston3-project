<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unReadNotifications()
    {
        $notifications = array();

        //알림의 내용을 배열에 담에서 return
        foreach (auth()->user()->unReadNotifications as $notification) {
            array_push($notifications, $notification->data['data']);
        }
        return $notifications;
    }

    public function ReadNotifications()
    {
        $notifications = array();

        //알림의 내용을 배열에 담에서 return
        foreach (auth()->user()->ReadNotifications as $notification) {
            array_push($notifications, $notification);
        }
        return $notifications;
    }
}
