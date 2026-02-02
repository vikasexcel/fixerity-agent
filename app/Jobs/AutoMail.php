<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class AutoMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $email = $data['email'];
        $path = $data['path'];
        $subject = $data['subject'];
        $mail_site_name = $data['mail_site_name'];
        $smtp_user_name = $data['smtp_user_name'];
        $smtp_password = $data['smtp_password'];
        $smtp_hostname = $data['smtp_hostname'];
        $smtp_port = $data['smtp_port'];
        $smtp_encryption = $data['smtp_encryption'];

        Config::set('mail.mailers.smtp.username', $smtp_user_name);
        Config::set('mail.mailers.smtp.password', $smtp_password);
        Config::set('mail.mailers.smtp.host', $smtp_hostname);
        Config::set('mail.mailers.smtp.port', $smtp_port);
        Config::set('mail.mailers.smtp.encryption', $smtp_encryption);

        Mail::send($path, $data, function ($message) use ($email, $subject,$mail_site_name, $smtp_user_name) {
            $message->from($smtp_user_name, $mail_site_name);
            //$message->from('hello@app.com', $mail_site_name);
            $message->to($email);
            $message->subject($subject);
        });
    }
}
