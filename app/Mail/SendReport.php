<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendReport extends Mailable
{
    use Queueable, SerializesModels;

    private $file_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($file_url)
    {
        $this->file_url = $file_url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Informe finalizado '.date('d-m-Y H:i:s'))
            ->view('emails.send_report', [
                'file_url' => $this->file_url
            ]);
    }
}
