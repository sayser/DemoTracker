<?php

declare(strict_types=1);

final class WatchService
{
    public static function subscriberEmails(int $vmId): array
    {
        $sql = 'SELECT u.email
                FROM users u
                WHERE u.active = 1
                  AND (
                    EXISTS (SELECT 1 FROM watch_global g WHERE g.user_id = u.id)
                    OR EXISTS (SELECT 1 FROM watch_vm v WHERE v.user_id = u.id AND v.vm_id = ?)
                  )
                ORDER BY u.email';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$vmId]);
        $emails = [];
        foreach ($stmt->fetchAll() as $row) {
            $email = strtolower(trim((string) $row['email']));
            if ($email !== '') {
                $emails[$email] = $email;
            }
        }
        return array_values($emails);
    }

    public static function isGlobal(int $userId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM watch_global WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function vmIds(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT vm_id FROM watch_vm WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function save(int $userId, bool $global, array $vmIds): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $delG = $pdo->prepare('DELETE FROM watch_global WHERE user_id = ?');
            $delG->execute([$userId]);
            if ($global) {
                $insG = $pdo->prepare('INSERT INTO watch_global (user_id) VALUES (?)');
                $insG->execute([$userId]);
            }

            $delV = $pdo->prepare('DELETE FROM watch_vm WHERE user_id = ?');
            $delV->execute([$userId]);
            $insV = $pdo->prepare('INSERT INTO watch_vm (user_id, vm_id) VALUES (?, ?)');
            foreach (array_unique($vmIds) as $vmId) {
                $id = (int) $vmId;
                if ($id > 0) {
                    $insV->execute([$userId, $id]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
