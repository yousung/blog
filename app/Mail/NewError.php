<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewError extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;


    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('[러비뷰] 에러발생')
            ->view('emails.exception')->with('content', $this->content);
    }
}
