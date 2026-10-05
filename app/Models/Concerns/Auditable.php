<?php

namespace App\Models\Concerns;

use App\Services\AuditService;

/**
 * Otomatis mencatat perubahan penting pada model.
 *
 * PRD 02 §27 mewajibkan audit untuk create/update/delete master penting,
 * perubahan harga, dan perubahan sensitif. Kegagalan audit tidak boleh
 * menggagalkan operasi bisnis utama.
 */
trait Auditable
{
    /**
     * Atribut yang direkam saat create/update/delete. Override di model.
     * Kosong = semua atribut.
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [];
    }

    /**
     * Nilai asli sebelum update terakhir, diambil saat event `updating`.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $auditOriginal = null;

    public function auditService(): AuditService
    {
        return app(AuditService::class);
    }

    protected static function bootAuditable(): void
    {
        static::created(function (self $model) {
            self::guardAudit(fn () => $model->auditService()->created($model, $model->auditAttributes()));
        });

        static::updating(function (self $model) {
            $model->auditOriginal = $model->getOriginal();
        });

        static::updated(function (self $model) {
            $changed = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));

            $tracked = $model->auditAttributes();
            if ($tracked !== []) {
                $changed = array_values(array_intersect($changed, $tracked));
            }

            if ($changed === []) {
                return;
            }

            $oldValues = self::pickKeys($model->auditOriginal ?? [], $changed);

            self::guardAudit(
                fn () => $model->auditService()->updated($model, $changed, $oldValues)
            );
        });

        static::deleted(function (self $model) {
            self::guardAudit(fn () => $model->auditService()->deleted($model, $model->auditAttributes()));
        });
    }

    /**
     * Jalankan pencatatan audit tanpa mempropagasi error ke operasi bisnis.
     */
    private static function guardAudit(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private static function pickKeys(array $values, array $keys): array
    {
        $picked = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                $picked[$key] = $values[$key];
            }
        }

        return $picked;
    }
}