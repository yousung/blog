<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewError extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;


    private $content, $url;

    public function __construct($content, $url)
    {
        $this->content = $content;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $url = $this->url;
        return $this
            ->subject("[ 러비쥬 | ERROR ] {$url}")
            ->view('emails.exception')->with('content', $this->content);
    }
}
