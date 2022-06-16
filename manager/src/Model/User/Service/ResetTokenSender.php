<?php

declare(strict_types=1);

namespace App\Model\User\Service;

use App\Model\User\Entity\User\Email;
use App\Model\User\Entity\User\ResetToken;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twig\Environment;

class ResetTokenSender
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

    public function send(Email $email, ResetToken $token): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->from($this->from)
            ->to($email->getValue())
            ->html($this->twig->render('mail/user/reset.html.twig', [
                'token' => $token->getToken()
            ]));

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Unable to send message.');
        }
    }
}
