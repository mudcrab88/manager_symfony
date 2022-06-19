<?php

declare(strict_types=1);

namespace App\Model\User\Service;

use App\Model\User\Entity\User\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class ConfirmTokenSender
{
    private $mailer;
    private $twig;
    private $from;

    public function __construct(MailerInterface $mailer, Environment $twig, string $from)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->from = $from;
    }

    public function send(Email $email, string $token): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->from($this->from)
            ->to($email->getValue())
            ->subject('Sig Up Confirmation!')
            ->html($this->twig->render('mail/user/signup.html.twig', [
                'token' => $token
            ]));

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Unable to send message.');
        }
    }
}
