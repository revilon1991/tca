<?php

declare(strict_types=1);

namespace App\UseCase\SendMail;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendMailHandler
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var string
     */
    private $mailerDsn;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MailerInterface $mailer
     * @param string $mailerDsn
     * @param LoggerInterface $logger
     */
    public function __construct(MailerInterface $mailer, string $mailerDsn, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->mailerDsn = $mailerDsn;
        $this->logger = $logger;
    }

    /**
     * @param string $email
     *
     * @param string $subject
     * @param string $text
     */
    public function handle(string $email, string $subject, string $text): void
    {
        $mailerDsnPartList = parse_url($this->mailerDsn);
        $adminEmail = $mailerDsnPartList['user'];

        $mail = (new Email())
            ->from($adminEmail)
            ->to($email)
            ->subject($subject)
            ->text($text)
        ;

        try {
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $exception) {
            $message = "Can not send email message for $email: {$exception->getMessage()}";

            $this->logger->error($message);
        }
    }
}
