<?php
function status_badge(string $status): string
{
    $label = match ($status) {
        'active' => 'Active',
        'expired' => 'Expired',
        'released' => 'Released',
        default => $status,
    };
    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

function window_et(array $row): string
{
    $s = Timezone::formatEt(Timezone::fromUtcString($row['start_utc']));
    $e = Timezone::formatEt(Timezone::fromUtcString($row['end_utc']));
    return e($s) . ' → ' . e($e);
}
