<?php
declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message ?: match ($status) {
            403 => 'You do not have permission to do this.',
            404 => 'The page or record was not found, or you are not allowed to see it.',
            419 => 'Your form expired. Please go back, refresh the page and try again.',
            422 => 'Please correct the highlighted fields.',
            default => 'Something went wrong.',
        });
    }
}
