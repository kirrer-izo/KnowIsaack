<?php

namespace App\Domain\Interfaces;

interface EmailServiceInterface
{
    public function sendEmail($to, $subject, $body, $replyTo = null);
}