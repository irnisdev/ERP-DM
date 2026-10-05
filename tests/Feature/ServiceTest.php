<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;
use App\Models\User;
use App\Services\ServicePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §10 — Service workflow: category, service, pricing method, price rules.
 * Duration pricing dan weight pricing punya aturan berbeda.
 */
class ServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions, string $role = 'ADMIN'): User
    {
        $user = User::factory()->create();

        $roleModel = \App\Models\Role::firstOrCreate(
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

    private function payload(array $overrides = []): array
    {
        $category = ServiceCategory::factory()->create();

        return array_merge([
            'service_code' => 'SRV-MASSAGE',
            'category_id' => $category->id,
            'name' => 'Massage 60 Menit',
            'pricing_method' => Service::PRICING_FIXED,
            'status' => 'ACTIVE',
        ], $overrides);
    }

    #[Test]
    public function store_creates_service(): void
    {
        $user = $this->userWith(['service.manage']);

        $this->actingAs($user)
            ->post('/services', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('services', ['service_code' => 'SRV-MASSAGE']);
    }

    #[Test]
    public function duplicate_service_code_is_rejected(): void
    {
        Service::factory()->create(['service_code' => 'SRV-MASSAGE']);

        $this->actingAs($this->userWith(['service.manage']))
            ->post('/services', $this->payload())
            ->assertSessionHasErrors('service_code');
    }

    #[Test]
    public function duration_pricing_requires_default_duration(): void
    {
        $this->actingAs($this->userWith(['service.manage']))
            ->post('/services', $this->payload([
                'pricing_method' => Service::PRICING_DURATION,
                'default_duration_minutes' => null,
            ]))
            ->assertSessionHasErrors('default_duration_minutes');
    }

    #[Test]
    public function user_without_permission_cannot_create_service(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/services', $this->payload())
            ->assertForbidden();
    }

    // ---- Duration pricing

    #[Test]
    public function duration_price_can_be_added(): void
    {
        $service = Service::factory()->duration()->create();
        $user = $this->userWith(['service.manage']);

        $this->actingAs($user)
            ->post("/services/{$service->id}/prices", [
                'duration_minutes' => 60,
                'price' => 180000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('service_prices', [
            'service_id' => $service->id,
            'duration_minutes' => 60,
            'price' => 180000,
        ]);
    }

    #[Test]
    public function duration_price_requires_duration_minutes(): void
    {
        $service = Service::factory()->duration()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'price' => 180000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('duration_minutes');
    }

    #[Test]
    public function duplicate_duration_price_in_same_period_is_rejected(): void
    {
        $service = Service::factory()->duration()->create();
        $pricing = app(ServicePricingService::class);

        $pricing->addPrice($service, [
            'duration_minutes' => 60,
            'price' => 180000,
            'effective_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'duration_minutes' => 60,
                'price' => 200000,
                'effective_date' => now()->addMonth()->toDateString(),
            ])
            ->assertSessionHasErrors('duration_minutes');

        $this->assertSame(1, $service->prices()->count());
    }

    #[Test]
    public function duration_prices_resolve_correctly(): void
    {
        $service = Service::factory()->duration()->create(['default_duration_minutes' => 60]);
        $pricing = app(ServicePricingService::class);

        $pricing->addPrice($service, ['duration_minutes' => 60, 'price' => 180000, 'effective_date' => now()->toDateString()]);
        $pricing->addPrice($service, ['duration_minutes' => 90, 'price' => 250000, 'effective_date' => now()->toDateString()]);

        $this->assertEquals(180000.0, (float) $pricing->resolvePrice($service, 60)->price);
        $this->assertEquals(250000.0, (float) $pricing->resolvePrice($service, 90)->price);
        $this->assertEquals(180000.0, (float) $pricing->resolvePrice($service)->price, 'Default duration dipakai bila tidak diisi');
    }

    // ---- Weight pricing

    #[Test]
    public function weight_price_requires_range(): void
    {
        $service = Service::factory()->weight()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'price' => 200000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors(['min_weight', 'max_weight']);
    }

    #[Test]
    public function weight_price_range_can_be_added(): void
    {
        $service = Service::factory()->weight()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'min_weight' => 40,
                'max_weight' => 50,
                'price' => 200000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('service_prices', [
            'service_id' => $service->id,
            'min_weight' => 40,
            'max_weight' => 50,
        ]);
    }

    #[Test]
    public function overlapping_weight_range_is_rejected(): void
    {
        $service = Service::factory()->weight()->create();
        $pricing = app(ServicePricingService::class);

        // 40–50 kg
        $pricing->addPrice($service, [
            'min_weight' => 40, 'max_weight' => 50,
            'price' => 200000, 'effective_date' => now()->toDateString(),
        ]);

        // 45–55 kg overlap
        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'min_weight' => 45, 'max_weight' => 55,
                'price' => 220000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('max_weight');

        $this->assertSame(1, $service->prices()->count());
    }

    #[Test]
    public function adjacent_weight_range_is_allowed(): void
    {
        $service = Service::factory()->weight()->create();
        $pricing = app(ServicePricingService::class);

        $pricing->addPrice($service, [
            'min_weight' => 40, 'max_weight' => 50,
            'price' => 200000, 'effective_date' => now()->toDateString(),
        ]);

        // 51–60 tidak overlap dengan 40–50
        $pricing->addPrice($service, [
            'min_weight' => 51, 'max_weight' => 60,
            'price' => 250000, 'effective_date' => now()->toDateString(),
        ]);

        $this->assertSame(2, $service->prices()->count());
    }

    #[Test]
    public function same_weight_range_in_non_overlapping_period_is_allowed(): void
    {
        $service = Service::factory()->weight()->create();
        $pricing = app(ServicePricingService::class);

        $pricing->addPrice($service, [
            'min_weight' => 40, 'max_weight' => 50,
            'price' => 200000,
            'effective_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        // Periode 2025 tidak overlap dengan 2024
        $pricing->addPrice($service, [
            'min_weight' => 40, 'max_weight' => 50,
            'price' => 250000,
            'effective_date' => '2025-01-01',
        ]);

        $this->assertSame(2, $service->prices()->count());
    }

    #[Test]
    public function weight_price_resolves_by_weight(): void
    {
        $service = Service::factory()->weight()->create();
        $pricing = app(ServicePricingService::class);

        $pricing->addPrice($service, ['min_weight' => 40, 'max_weight' => 50, 'price' => 200000, 'effective_date' => now()->toDateString()]);
        $pricing->addPrice($service, ['min_weight' => 51, 'max_weight' => 60, 'price' => 250000, 'effective_date' => now()->toDateString()]);

        $this->assertEquals(200000.0, (float) $pricing->resolvePrice($service, null, 45)->price);
        $this->assertEquals(250000.0, (float) $pricing->resolvePrice($service, null, 55)->price);
    }

    #[Test]
    public function weight_fields_are_ignored_for_duration_pricing(): void
    {
        $service = Service::factory()->duration()->create();
        $pricing = app(ServicePricingService::class);

        $price = $pricing->addPrice($service, [
            'duration_minutes' => 60,
            'min_weight' => 40,
            'max_weight' => 50,
            'price' => 180000,
            'effective_date' => now()->toDateString(),
        ]);

        $this->assertNull($price->min_weight);
        $this->assertNull($price->max_weight);
    }

    #[Test]
    public function min_weight_greater_than_max_weight_is_rejected(): void
    {
        $service = Service::factory()->weight()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'min_weight' => 60, 'max_weight' => 40,
                'price' => 200000,
                'effective_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('max_weight');
    }

    // ---- Aturan umum

    #[Test]
    public function service_with_sales_history_cannot_be_deleted(): void
    {
        $service = Service::factory()->create();

        \App\Models\SalesTransactionItem::create([
            'sales_transaction_id' => \App\Models\SalesTransaction::factory()->create()->id,
            'item_type' => \App\Models\SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'item_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 180000,
            'subtotal' => 180000,
        ]);

        $this->actingAs($this->userWith(['service.manage']))
            ->delete('/services/'.$service->id)
            ->assertForbidden();
    }

    #[Test]
    public function unused_service_can_be_soft_deleted(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->delete('/services/'.$service->id)
            ->assertRedirect(route('services.index'));

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    #[Test]
    public function price_change_does_not_affect_existing_sales_item(): void
    {
        $service = Service::factory()->duration()->create();
        $pricing = app(ServicePricingService::class);
        $pricing->addPrice($service, ['duration_minutes' => 60, 'price' => 180000, 'effective_date' => now()->toDateString()]);

        $transaction = \App\Models\SalesTransaction::factory()->create();
        $item = \App\Models\SalesTransactionItem::create([
            'sales_transaction_id' => $transaction->id,
            'item_type' => \App\Models\SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'item_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 180000,
            'subtotal' => 180000,
        ]);

        // Harga master berubah: periode lama ditutup, periode baru dibuka.
        $service->prices()->update(['end_date' => now()->toDateString()]);

        $pricing->addPrice($service, [
            'duration_minutes' => 60, 'price' => 250000,
            'effective_date' => now()->addDay()->toDateString(),
        ]);

        // Transaksi lama tetap memakai harga lama
        $this->assertEquals(180000.0, (float) $item->fresh()->unit_price);
    }

    #[Test]
    public function service_price_change_is_audited(): void
    {
        $service = Service::factory()->duration()->create();

        $this->actingAs($this->userWith(['service.manage']))
            ->post("/services/{$service->id}/prices", [
                'duration_minutes' => 60,
                'price' => 180000,
                'effective_date' => now()->toDateString(),
            ]);

        $price = ServicePrice::where('service_id', $service->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ServicePrice::class,
            'auditable_id' => $price->id,
            'action' => 'created',
        ]);
    }

    #[Test]
    public function service_creation_is_audited(): void
    {
        $this->actingAs($this->userWith(['service.manage']))
            ->post('/services', $this->payload());

        $service = Service::where('service_code', 'SRV-MASSAGE')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Service::class,
            'auditable_id' => $service->id,
            'action' => 'created',
        ]);
    }
}