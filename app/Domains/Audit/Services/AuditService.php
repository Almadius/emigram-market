<?php

declare(strict_types=1);

namespace App\Domains\Audit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AuditService
{
    public function log(string $action, int $userId, array $data = []): void
    {
        DB::table('audit_logs')->insert([
            'action' => $action,
            'user_id' => $userId,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        Log::info('Audit log', [
            'action' => $action,
            'user_id' => $userId,
            'data' => $data,
        ]);
    }

    public function anonymizeUserData(int $userId): void
    {
        // GDPR: Anonymize user data
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'email' => "deleted_{$userId}@anonymized.local",
                'name' => "User {$userId}",
            ]);
    }
}




