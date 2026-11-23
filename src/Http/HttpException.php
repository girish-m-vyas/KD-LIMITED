<?php
declare(strict_types=1);

namespace KD\Http;

final class HttpException extends \RuntimeException
{
    public static function notFound(): self
    {
        return new self('Page not found.', 404);
    }

    public static function forbidden(string $message = 'You do not have permission to do that.'): self
    {
        return new self($message, 403);
    }
}
