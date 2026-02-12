<?php 

namespace App\Infrastructure\Mail;

use App\Domain\Interfaces\EmailServiceInterface;
use Resend;

class ResendMailer implements EmailServiceInterface {

    public function sendEmail($to, $subject, $body, $replyTo = null)
    {
        $resend = Resend::client(getenv('RESEND_API_KEY'));

        try {
        $resend->emails->send([
        'from' => 'Portfolio <onboarding@resend.dev> ',
        'to' => $to,
        'subject' => $subject,
        'reply_to' => $replyTo,
        'html' => $body,
        ]);

        return true;
    } catch (Exception $e) {
        return false;
    }

    }
}