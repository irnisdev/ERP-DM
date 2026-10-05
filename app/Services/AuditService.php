<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit log untuk master data penting, perubahan harga, dan perubahan sensitif
 * (PRD 02 §27). Nilai yang disimpan adalah snapshot, bukan referensi model,
 * sehingga histori tidak berubah meski master di-update lagi.
 */
class AuditService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null): ?AuditLog
    {
        try {
            return AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $auditable->getMorphClass(),
                'auditable_id' => $auditable->getKey(),
                'old_values' => $oldValues === [] ? null : $oldValues,
                'new_values' => $newValues === [] ? null : $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 500) ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @param  array<int, string>  $attributes
     */
    public function created(Model $auditable, array $attributes = []): ?AuditLog
    {
        return $this->log(
            'created',
            $auditable,
            null,
            $this->snapshot($auditable, $attributes)
        );
    }

    /**
     * @param  array<int, string>  $changedAttributes
     */
    public function updated(Model $auditable, array $changedAttributes, ?array $oldSnapshot = null): ?AuditLog
    {
        return $this->log(
            'updated',
            $auditable,
            $this->snapshot($auditable, $changedAttributes, $oldSnapshot),
            $this->snapshot($auditable, $changedAttributes)
        );
    }

    /**
     * @param  array<int, string>  $attributes
     */
    public function deleted(Model $auditable, array $attributes = []): ?AuditLog
    {
        return $this->log(
            'deleted',
            $auditable,
            $this->snapshot($auditable, $attributes),
            null
        );
    }

    public function login(Model $user, bool $successful): ?AuditLog
    {
        return $this->log($successful ? 'login' : 'login_failed', $user);
    }

    /**
     * @param  array<int, string>  $attributes
     * @param  array<string, mixed>|null  $source
     * @return array<string, mixed>
     */
    private function snapshot(Model $model, array $attributes, ?array $source = null): array
    {
        $source ??= $model->getAttributes();

        if ($attributes === []) {
            return $source;
        }

        $filtered = [];
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $source)) {
                $filtered[$attribute] = $source[$attribute];
            }
        }

        return $filtered;
    }
}