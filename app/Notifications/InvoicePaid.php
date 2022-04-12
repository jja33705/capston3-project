<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $type;
    public $user_id;
    public $post_id;

    public function __construct($type, $user_id)
    {
        $this->type = $type;
        $this->user_id = $user_id;
        // $this->post_id = $post_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('ari', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        // return $this->type;
        return [
            'id' => $this->type,
            'content' => "followed you!"
            // 'data' => $this->id + "followed you!",
        ];
        // if ($this->type == 'follow') {
        //     return 1;
        //     if (User::with('followers')->find($this->id)) {
        //         return [
        //             'data' => $this->id + "followed you!",
        //         ];
        //     };
        // } else if ($this->type == 'like') {
        //     return [
        //         'data4' => $this->test,
        //         'data' => 'This is my first notification'
        //     ];
        // } else {
        //     return [
        //         'data4' => $this->test,
        //         'data' => 'This is my first notification'
        //     ];
        // }
    }
}
