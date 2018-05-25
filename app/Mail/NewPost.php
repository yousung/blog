<?php

namespace App\Mail;

use App\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPost extends Mailable
{
    use Queueable, SerializesModels;

    private $post;
    private $userName;

    public function __construct(Post $post, $userName)
    {
        $this->post = $post;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $post = $this->post;
        $name = $this->userName;

        return $this->from('help@lovizu.com')
            ->subject("[ 러비쥬 | 새글알림 ] {$post->title}")
            ->markdown('emails.post', compact('post', 'name'));
    }
}
