<?php

declare(strict_types=1);

final class Timezone
{
    public const APP_TZ = 'America/New_York';

    public static function appTz(): DateTimeZone
    {
        global $config;
        $name = (string) ($config['app']['timezone'] ?? self::APP_TZ);
        return new DateTimeZone($name);
    }

    public static function utcTz(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }

    public static function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::utcTz());
    }

    public static function nowApp(): DateTimeImmutable
    {
        return self::nowUtc()->setTimezone(self::appTz());
    }

    public static function fromUtcString(string $utc): DateTimeImmutable
    {
        return new DateTimeImmutable($utc, self::utcTz());
    }

    public static function toApp(DateTimeImmutable $utc): DateTimeImmutable
    {
        return $utc->setTimezone(self::appTz());
    }

    public static function formatEt(DateTimeImmutable $utc): string
    {
        return self::toApp($utc)->format('M j, Y g:i A') . ' ET';
    }

    public static function formatEtShort(DateTimeImmutable $utc): string
    {
        return self::toApp($utc)->format('M j, g:i A') . ' ET';
    }

    public static function parseAppLocal(string $local): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $local, self::appTz());
        if ($dt === false) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $local, self::appTz());
        }
        if ($dt === false) {
            throw new InvalidArgumentException('Invalid date and time.');
        }
        return $dt->setTimezone(self::utcTz());
    }

    public static function toLocalInput(DateTimeImmutable $utc): string
    {
        return self::toApp($utc)->format('Y-m-d\TH:i');
    }

    public static function utcSql(DateTimeImmutable $utc): string
    {
        return $utc->setTimezone(self::utcTz())->format('Y-m-d H:i:s');
    }
}
