<?php

namespace App\Listeners;

use App\Events\EmailSender;
use App\Mail\NewPost;
use App\Subscribe;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailSenderListener implements ShouldQueue
{
    public function handle(EmailSender $event)
    {
        $post = $event->post;
        $sendMail = [];

        foreach (Subscribe::all() as $user) {
            if ($user->name && $user->email) {
                if (!in_array($user->email, $sendMail)) {
                    // 중복방지
                    $sendMail = $user->email;
                    \Mail::to($user->email)->send(new NewPost($post, $user->name));
                }
            }
        }
    }
}
