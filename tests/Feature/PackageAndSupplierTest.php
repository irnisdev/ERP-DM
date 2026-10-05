<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §10 Package domain dan §11 Supplier. Nomor rekening adalah data sensitif
 * (PRD 02 §28) sehingga butuh permission sensitive.rekening.
 */
class PackageAndSupplierTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions, string $role = 'ADMIN'): User
    {
        $user = User::factory()->create();

        $roleModel = Role::firstOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['description' => $role]
        );

        $ids = [];
        foreach ($permissions as $name) {
            $ids[] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $name]
            )->id;
        }

        $roleModel->permissions()->syncWithoutDetaching($ids);
        $user->roles()->syncWithoutDetaching([$roleModel->id]);

        return $user->fresh();
    }

    // ---------------- Package

    /**
     * @return array<string, mixed>
     */
    private function packagePayload(array $overrides = [], array $services = null): array
    {
        $services ??= [
            ['service_id' => Service::factory()->create()->id, 'quantity' => 1],
        ];

        return array_merge([
            'code' => 'PKG-3X',
            'name' => 'Paket Treatment 3x',
            'price' => 500000,
            'status' => 'ACTIVE',
            'services' => $services,
        ], $overrides);
    }

    #[Test]
    public function package_can_be_created_with_services(): void
    {
        $serviceA = Service::factory()->create();
        $serviceB = Service::factory()->create();

        $this->actingAs($this->userWith(['package.manage']))
            ->post('/packages', $this->packagePayload([], [
                ['service_id' => $serviceA->id, 'quantity' => 2],
                ['service_id' => $serviceB->id, 'quantity' => 1],
            ]))
            ->assertSessionHasNoErrors();

        $package = ServicePackage::where('code', 'PKG-3X')->firstOrFail();

        $this->assertCount(2, $package->services);
        $this->assertSame(2.0, (float) $package->services->firstWhere('id', $serviceA->id)->pivot->quantity);
    }

    #[Test]
    public function package_requires_at_least_one_service(): void
    {
        $this->actingAs($this->userWith(['package.manage']))
            ->post('/packages', $this->packagePayload([], []))
            ->assertSessionHasErrors('services');
    }

    #[Test]
    public function package_rejects_duplicate_service_in_same_package(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->userWith(['package.manage']))
            ->post('/packages', $this->packagePayload([], [
                ['service_id' => $service->id, 'quantity' => 1],
                ['service_id' => $service->id, 'quantity' => 2],
            ]))
            ->assertSessionHasErrors('services');
    }

    #[Test]
    public function package_update_replaces_items(): void
    {
        $oldService = Service::factory()->create();
        $newService = Service::factory()->create();

        $this->actingAs($this->userWith(['package.manage']))
            ->post('/packages', $this->packagePayload([], [
                ['service_id' => $oldService->id, 'quantity' => 1],
            ]))
            ->assertSessionHasNoErrors();

        $package = ServicePackage::where('code', 'PKG-3X')->firstOrFail();

        $this->actingAs($this->userWith(['package.manage']))
            ->put("/packages/{$package->id}", $this->packagePayload([], [
                ['service_id' => $newService->id, 'quantity' => 3],
            ]))
            ->assertSessionHasNoErrors();

        $package->refresh()->load('services');

        $this->assertCount(1, $package->services);
        $this->assertSame($newService->id, $package->services->first()->id);
        $this->assertDatabaseMissing('service_package_items', [
            'package_id' => $package->id,
            'service_id' => $oldService->id,
        ]);
    }

    #[Test]
    public function duplicate_package_code_is_rejected(): void
    {
        ServicePackage::factory()->create(['code' => 'PKG-3X']);

        $this->actingAs($this->userWith(['package.manage']))
            ->post('/packages', $this->packagePayload())
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function package_with_sales_history_cannot_be_deleted(): void
    {
        $package = ServicePackage::factory()->create();
        $service = Service::factory()->create();
        $package->services()->attach($service->id, ['quantity' => 1]);

        \App\Models\SalesTransactionItem::create([
            'sales_transaction_id' => \App\Models\SalesTransaction::factory()->create()->id,
            'item_type' => \App\Models\SalesTransactionItem::TYPE_PACKAGE,
            'package_id' => $package->id,
            'item_name' => $package->name,
            'quantity' => 1,
            'unit_price' => 500000,
            'subtotal' => 500000,
        ]);

        $this->actingAs($this->userWith(['package.manage']))
            ->delete('/packages/'.$package->id)
            ->assertForbidden();
    }

    #[Test]
    public function unused_package_can_be_soft_deleted(): void
    {
        $package = ServicePackage::factory()->create();

        $this->actingAs($this->userWith(['package.manage']))
            ->delete('/packages/'.$package->id)
            ->assertRedirect(route('packages.index'));

        $this->assertSoftDeleted('service_packages', ['id' => $package->id]);
    }

    // ---------------- Supplier

    /**
     * @return array<string, mixed>
     */
    private function supplierPayload(array $overrides = []): array
    {
        return array_merge([
            'supplier_code' => 'SUP-001',
            'name' => 'PT Sumber Naturals',
            'contact_person' => 'Pak Budi',
            'phone' => '021-5551234',
            'payment_terms_days' => 30,
            'status' => 'ACTIVE',
        ], $overrides);
    }

    #[Test]
    public function supplier_can_be_created(): void
    {
        $this->actingAs($this->userWith(['supplier.manage']))
            ->post('/suppliers', $this->supplierPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', ['supplier_code' => 'SUP-001']);
    }

    #[Test]
    public function duplicate_supplier_code_is_rejected(): void
    {
        Supplier::factory()->create(['supplier_code' => 'SUP-001']);

        $this->actingAs($this->userWith(['supplier.manage']))
            ->post('/suppliers', $this->supplierPayload())
            ->assertSessionHasErrors('supplier_code');
    }

    #[Test]
    public function supplier_with_purchase_order_cannot_be_deleted(): void
    {
        $supplier = Supplier::factory()->create();

        \App\Models\PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

        $this->actingAs($this->userWith(['supplier.manage']))
            ->delete('/suppliers/'.$supplier->id)
            ->assertForbidden();
    }

    #[Test]
    public function bank_account_requires_sensitive_permission(): void
    {
        $supplier = Supplier::factory()->create();

        // Hanya supplier.manage, tanpa sensitive.rekening
        $this->actingAs($this->userWith(['supplier.manage']))
            ->post("/suppliers/{$supplier->id}/bank-accounts", [
                'bank_id' => Bank::factory()->create()->id,
                'account_number' => '1234567890',
                'account_name' => 'PT Sumber Naturals',
                'status' => 'ACTIVE',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function bank_account_can_be_added_with_sensitive_permission(): void
    {
        $supplier = Supplier::factory()->create();
        $bank = Bank::factory()->create();

        $this->actingAs($this->userWith(['supplier.manage', 'sensitive.rekening']))
            ->post("/suppliers/{$supplier->id}/bank-accounts", [
                'bank_id' => $bank->id,
                'account_number' => '1234567890',
                'account_name' => 'PT Sumber Naturals',
                'branch_name' => 'Krean Depok',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supplier_bank_accounts', [
            'supplier_id' => $supplier->id,
            'account_number' => '1234567890',
        ]);
    }

    #[Test]
    public function duplicate_account_number_per_supplier_is_rejected(): void
    {
        $supplier = Supplier::factory()->create();

        SupplierBankAccount::create([
            'supplier_id' => $supplier->id,
            'bank_id' => Bank::factory()->create()->id,
            'account_number' => '1234567890',
            'account_name' => 'PT Sumber',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->userWith(['supplier.manage', 'sensitive.rekening']))
            ->post("/suppliers/{$supplier->id}/bank-accounts", [
                'bank_id' => Bank::factory()->create()->id,
                'account_number' => '1234567890',
                'account_name' => 'PT Sumber',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('account_number');
    }

    #[Test]
    public function same_account_number_allowed_for_different_supplier(): void
    {
        $supplierA = Supplier::factory()->create();
        $supplierB = Supplier::factory()->create();

        SupplierBankAccount::create([
            'supplier_id' => $supplierA->id,
            'bank_id' => Bank::factory()->create()->id,
            'account_number' => '1234567890',
            'account_name' => 'PT A',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->userWith(['supplier.manage', 'sensitive.rekening']))
            ->post("/suppliers/{$supplierB->id}/bank-accounts", [
                'bank_id' => Bank::factory()->create()->id,
                'account_number' => '1234567890',
                'account_name' => 'PT B',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function setting_primary_account_unsets_previous_one(): void
    {
        $supplier = Supplier::factory()->create();

        $first = SupplierBankAccount::create([
            'supplier_id' => $supplier->id,
            'bank_id' => Bank::factory()->create()->id,
            'account_number' => '1111111111',
            'account_name' => 'PT A',
            'is_primary' => true,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->userWith(['supplier.manage', 'sensitive.rekening']))
            ->post("/suppliers/{$supplier->id}/bank-accounts", [
                'bank_id' => Bank::factory()->create()->id,
                'account_number' => '2222222222',
                'account_name' => 'PT B',
                'is_primary' => true,
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $first->fresh()->is_primary);

        $new = SupplierBankAccount::where('account_number', '2222222222')->firstOrFail();
        $this->assertTrue((bool) $new->is_primary);
    }

    #[Test]
    public function user_without_rekening_permission_cannot_view_accounts(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->userWith(['supplier.view']))
            ->get("/suppliers/{$supplier->id}/bank-accounts")
            ->assertForbidden();
    }
}