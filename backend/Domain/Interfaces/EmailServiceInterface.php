<?php

namespace App\Domain\Interfaces;

interface EmailServiceInterface
{
    public function sendEmail($to, $subject, $body, $replyTo = null);

    public function sendVerificationEmail(string $to, string $name, string $token): bool;
    
    public function sendPasswordResetEmail(string $to, string $name, string $token): bool;

}