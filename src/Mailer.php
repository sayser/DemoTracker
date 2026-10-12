<?php

declare(strict_types=1);

final class Mailer
{
    public static function send(string $to, string $subject, string $body): void
    {
        global $config;
        $to = Security::oneLine($to);
        $subject = Security::oneLine($subject);
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }
        $fromEmail = Security::oneLine((string) $config['app']['from_email']);
        $fromName = Security::oneLine((string) $config['app']['from_name']);
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }
        $encodedFrom = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($fromName, 'UTF-8')
            : $fromName;
        $headers = [
            'From: ' . $encodedFrom . ' <' . $fromEmail . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    public static function notifyWatchers(int $vmId, string $subject, string $body): void
    {
        foreach (WatchService::subscriberEmails($vmId) as $email) {
            self::send($email, $subject, $body);
        }
    }

    public static function reservationCreated(array $reservation, array $vm, array $owner): void
    {
        $subject = 'Reservation created: ' . $vm['name'];
        $body = self::body(
            'A reservation was created.',
            $reservation,
            $vm,
            $owner
        );
        self::notifyWatchers((int) $vm['id'], $subject, $body);
    }

    public static function reservationExpired(array $reservation, array $vm, array $owner): void
    {
        $subject = 'Reservation expired: ' . $vm['name'];
        $body = self::body(
            'A reservation has expired. The Demo VM is available for new bookings in that window.',
            $reservation,
            $vm,
            $owner
        );
        self::notifyWatchers((int) $vm['id'], $subject, $body);
    }

    public static function reservationReleased(array $reservation, array $vm, array $owner, array $releasedBy): void
    {
        $subject = 'Reservation released: ' . $vm['name'];
        $extra = 'Released early by ' . $releasedBy['username'] . '.';
        $body = self::body(
            'A reservation was released before it expired. The Demo VM is available for new bookings in that window.',
            $reservation,
            $vm,
            $owner,
            $extra
        );
        self::notifyWatchers((int) $vm['id'], $subject, $body);
    }

    private static function body(string $intro, array $reservation, array $vm, array $owner, string $extra = ''): string
    {
        $start = Timezone::formatEt(Timezone::fromUtcString($reservation['start_utc']));
        $end = Timezone::formatEt(Timezone::fromUtcString($reservation['end_utc']));
        $lines = [
            'Demo Reservation',
            '',
            $intro,
        ];
        if ($extra !== '') {
            $lines[] = $extra;
        }
        $lines[] = '';
        $lines[] = 'Demo VM: ' . $vm['name'];
        if (!empty($vm['ip_hostname'])) {
            $lines[] = 'IP / hostname: ' . $vm['ip_hostname'];
        }
        $lines[] = 'Reserved by: ' . $owner['username'] . ' (' . $owner['email'] . ')';
        $lines[] = 'Start: ' . $start;
        $lines[] = 'End: ' . $end;
        $lines[] = 'Duration: ' . $reservation['duration_label'];
        if (!empty($reservation['notes'])) {
            $lines[] = 'Notes: ' . $reservation['notes'];
        }
        $lines[] = '';
        $lines[] = 'Times are Eastern Time (America/New_York).';
        return implode("\n", $lines);
    }
}
