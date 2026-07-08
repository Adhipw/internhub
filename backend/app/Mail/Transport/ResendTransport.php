<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class ResendTransport extends AbstractTransport
{
    protected string $key;

    public function __construct(string $key)
    {
        parent::__construct();
        $this->key = $key;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom();
        $to = $email->getTo();

        $params = [
            'from' => $this->formatAddress($from[0]),
            'to' => array_map([$this, 'formatAddress'], $to),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ];

        // Use Laravel's Http client with SSL verification disabled for local development
        $response = Http::withToken($this->key)
            ->timeout((int) config('mail.timeout', 10))
            ->withoutVerifying() // This fixes the cURL error 60 on Windows
            ->post('https://api.resend.com/emails', $params);

        if ($response->failed()) {
            Log::error('Resend API Error: '.$response->body());
            throw new \Exception('Resend API Error: ' . $response->body());
        }
    }

    protected function formatAddress(Address $address): string
    {
        return $address->getName()
            ? "{$address->getName()} <{$address->getAddress()}>"
            : $address->getAddress();
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
