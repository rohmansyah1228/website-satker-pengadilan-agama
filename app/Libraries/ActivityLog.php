<?php

namespace App\Libraries;

/** Log aktivitas admin untuk keperluan forensik (Bab II huruf G.2). */
class ActivityLog
{
    public static function add(string $action, string $subject, ?array $user = null): void
    {
        $user ??= admin_user();
        db_connect()->table('activity_logs')->insert([
            'user_id'    => $user['id'] ?? null,
            'username'   => $user['username'] ?? '-',
            'action'     => $action,
            'subject'    => mb_substr($subject, 0, 250),
            'ip'         => service('request')->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
