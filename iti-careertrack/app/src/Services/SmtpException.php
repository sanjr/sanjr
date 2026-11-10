<?php
declare(strict_types=1);

namespace App\Services;

final class SmtpException extends \RuntimeException
{
    public function __construct(public readonly int $smtpCode, string $message)
    {
        parent::__construct(mb_substr($message, 0, 250));
    }
}
