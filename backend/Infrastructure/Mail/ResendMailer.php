<?php 

namespace App\Infrastructure\Mail;

use App\Domain\Interfaces\EmailServiceInterface;
use Exception;
use Resend;

class ResendMailer implements EmailServiceInterface {

    public function sendEmail($to, $subject, $body, $replyTo = null)
    {
        $resend = Resend::client(getenv('RESEND_API_KEY'));

        try {
        $resend->emails->send([
        'from' => 'Portfolio <noreply@mail.isaack.me> ',
        'to' => $to,
        'subject' => $subject,
        'reply_to' => $replyTo,
        'html' => $body,
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Resend error: " . $e->getMessage());
        return false;
    }

    }

    // Send a verification email with a secure token link
    public function sendVerificationEmail(string $to, string $name, string $token): bool
    {
        $verificationUrl = getenv('APP_URL') . '/auth/verify?token=' . $token;

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0;padding:0;background:#f9fafb;font-family:sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#f9fafb;padding:40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='480' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;padding:40px;'>
                            <tr>
                                <td>
                                    <h1 style='font-size:24px;color:#111827;margin:0 0 8px;'>Verify your email</h1>
                                    <p style='color:#6b7280;font-size:15px;margin:0 0 24px;'>Hi {$name}, please verify your email address to access your account.</p>
                                    <a href='{$verificationUrl}' 
                                       style='display:inline-block;background:#2563eb;color:#ffffff;font-weight:700;font-size:15px;padding:14px 28px;border-radius:6px;text-decoration:none;'>
                                        Verify Email
                                    </a>
                                    <p style='color:#9ca3af;font-size:13px;margin:24px 0 0;'>This link expires in 24 hours. If you did not create an account, you can ignore this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
        
        return $this->sendEmail($to, 'Verify your email address', $html);
    }

        // Send a password reset email with a secure token link
    public function sendPasswordResetEmail(string $to, string $name, string $token): bool
    {
        $resetUrl = getenv('APP_URL') . '/auth/reset-password?token=' . $token;

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0;padding:0;background:#f9fafb;font-family:sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#f9fafb;padding:40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='480' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;padding:40px;'>
                            <tr>
                                <td>
                                    <h1 style='font-size:24px;color:#111827;margin:0 0 8px;'>Reset your password</h1>
                                    <p style='color:#6b7280;font-size:15px;margin:0 0 24px;'>Hi {$name}, we received a request to reset your password.</p>
                                    <a href='{$resetUrl}' 
                                    style='display:inline-block;background:#2563eb;color:#ffffff;font-weight:700;font-size:15px;padding:14px 28px;border-radius:6px;text-decoration:none;'>
                                        Reset Password
                                    </a>
                                    <p style='color:#9ca3af;font-size:13px;margin:24px 0 0;'>This link expires in 24 hours. If you did not request a password reset, you can ignore this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return $this->sendEmail($to, 'Reset your password', $html);
    }
}