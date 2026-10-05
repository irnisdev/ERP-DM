<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §27 — audit log untuk master penting, perubahan harga, dan perubahan sensitif.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        return Product::factory()->create([
            'category_id' => ProductCategory::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id,
        ]);
    }

    #[Test]
    public function creating_product_is_audited(): void
    {
        $product = $this->makeProduct();

        $log = AuditLog::forAuditable($product)->where('action', 'created')->first();

        $this->assertNotNull($log, 'Create product harus tercatat di audit log');
        $this->assertSame('created', $log->action);
        $this->assertSame(Product::class, $log->auditable_type);
        $this->assertSame($product->id, $log->auditable_id);
        $this->assertSame($product->sku, $log->new_values['sku']);
        $this->assertNull($log->old_values);
    }

    #[Test]
    public function updating_price_is_audited_with_old_and_new_values(): void
    {
        $product = $this->makeProduct();

        $product->update(['default_sell_price' => 999999]);

        $log = AuditLog::forAuditable($product)
            ->where('action', 'updated')
            ->whereJsonContains('new_values', ['default_sell_price' => 999999])
            ->first();

        $this->assertNotNull($log);
        $this->assertNotEquals(
            $log->old_values['default_sell_price'] ?? null,
            $log->new_values['default_sell_price'] ?? null,
            'Audit harus menyimpan nilai lama dan baru'
        );
    }

    #[Test]
    public function product_price_change_is_audited_separately(): void
    {
        $product = $this->makeProduct();
        $price = ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'RETAIL',
            'price' => 150000,
            'effective_date' => now()->toDateString(),
        ]);

        $this->assertNotNull(
            AuditLog::forAuditable($price)->where('action', 'created')->first(),
            'Perubahan harga wajib diaudit'
        );
    }

    #[Test]
    public function delete_is_audited_before_soft_delete(): void
    {
        $customer = Customer::factory()->create();

        $customer->delete();

        $log = AuditLog::forAuditable($customer)->where('action', 'deleted')->first();

        $this->assertNotNull($log);
        $this->assertSame($customer->customer_code, $log->old_values['customer_code'] ?? null);
    }

    #[Test]
    public function audit_records_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();

        Auth::login($user);

        $this->makeProduct();

        $log = AuditLog::query()->latest('id')->first();

        $this->assertSame($user->id, $log->user_id);
        $this->assertNotNull($log->ip_address);
    }

    #[Test]
    public function audit_does_not_break_business_operation_when_failing(): void
    {
        // Audit tidak boleh menggagalkan operasi bisnis utama
        $this->app->bind(\App\Services\AuditService::class, fn () => new class extends \App\Services\AuditService
        {
            public function log(string $action, \Illuminate\Database\Eloquent\Model $auditable, ?array $oldValues = null, ?array $newValues = null): ?AuditLog
            {
                throw new \RuntimeException('audit storage down');
            }
        });

        $product = Product::factory()->make();
        $originalSku = $product->sku;

        $product->category_id = ProductCategory::factory()->create()->id;
        $product->brand_id = Brand::factory()->create()->id;
        $product->unit_id = Unit::factory()->create()->id;
        $product->save();

        $this->assertTrue($product->exists, 'Create harus tetap sukses walau audit gagal');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => $originalSku]);
        $this->assertSame(0, AuditLog::query()->where('auditable_id', $product->id)->count());
    }

    #[Test]
    public function changes_helper_reports_only_differing_keys(): void
    {
        $log = new AuditLog([
            'old_values' => ['sku' => 'A', 'name' => 'X', 'price' => 100],
            'new_values' => ['sku' => 'A', 'name' => 'Y', 'price' => 150],
        ]);

        $changes = $log->changes();

        $this->assertSame(['name', 'price'], array_keys($changes));
        $this->assertSame(['from' => 'X', 'to' => 'Y'], $changes['name']);
    }

    #[Test]
    public function sensitive_master_data_change_is_audited(): void
    {
        $employee = Employee::factory()->create();

        $employee->update(['employment_status' => 'RESIGNED']);

        $log = AuditLog::forAuditable($employee)
            ->where('action', 'updated')
            ->whereJsonContains('new_values', ['employment_status' => 'RESIGNED'])
            ->first();

        $this->assertNotNull($log, 'Perubahan status employee wajib diaudit');
    }
}