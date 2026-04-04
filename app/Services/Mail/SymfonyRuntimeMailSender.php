<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTO\Mail\SendEmailData;
use App\DTO\Mail\TenantMailConfigData;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class SymfonyRuntimeMailSender implements RuntimeMailSenderInterface
{
    public function __construct(
        private RuntimeMailTransportFactory $transportFactory,
    ) {
    }

    public function send(TenantMailConfigData $config, SendEmailData $email): array
    {
        $transport = $this->transportFactory->make($config);
        $mailer = new Mailer($transport);

        $message = new Email();
        $message->from(new Address($config->fromAddress, $config->fromName));
        $message->subject($email->subject);

        foreach ($email->to as $recipient) {
            $message->addTo(new Address($recipient->email, $recipient->name ?? ''));
        }

        foreach ($email->cc as $recipient) {
            $message->addCc(new Address($recipient->email, $recipient->name ?? ''));
        }

        foreach ($email->bcc as $recipient) {
            $message->addBcc(new Address($recipient->email, $recipient->name ?? ''));
        }

        if ($config->replyToAddress !== null) {
            $message->replyTo(new Address($config->replyToAddress, $config->replyToName ?? ''));
        }

        if ($email->htmlBody !== null) {
            $message->html($email->htmlBody);
        }

        if ($email->textBody !== null) {
            $message->text($email->textBody);
        }

        try {
            $mailer->send($message);
        } catch (TransportExceptionInterface $exception) {
            throw $exception;
        }

        return [
            'provider_message_id' => $message->getHeaders()->get('Message-ID')?->getBodyAsString(),
        ];
    }
}