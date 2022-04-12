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

    public function __construct($type, $user_id, $id)
    {
        $this->type = $type;
        $this->user_id = $user_id;
        $this->post_id = $id;
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
        if ($this->type == 'follow') {
            return [
                'type' => $this->type,
                'user_id' => $this->user_id
            ];
        } else if ($this->type == 'like') {
            return [
                'type' => $this->type,
                'user_id' => $this->user_id,
                'post_id' => $this->post_id,

            ];
        } else {
            return [
                'type' => $this->type,
                'user_id' => $this->user_id,
                'post_id' => $this->post_id,
            ];
        }
    }
}
