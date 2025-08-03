<?php

namespace Kstmostofa\LaravelEsl;

enum EslConnectionErrors: int
{
    case ETIMEDOUT = 60;
    case ECONNREFUSED_A = 61; // Common on macOS
    case ECONNREFUSED_B = 111; // Common on Linux

    public function description(): string
    {
        return match ($this) {
            self::ETIMEDOUT => '(60) Operation timed out. This often means a firewall is silently blocking the connection or the destination server is not reachable.',
            self::ECONNREFUSED_A, self::ECONNREFUSED_B => '(' . $this->value . ') Connection refused. The server is reachable, but no service is listening on the specified port, or a firewall is actively rejecting the connection.',
        };
    }

    public static function getDescription(int $errno, string $fallback): string
    {
        $case = self::tryFrom($errno);
        return $case ? $case->description() : "($errno) $fallback";
    }
}
