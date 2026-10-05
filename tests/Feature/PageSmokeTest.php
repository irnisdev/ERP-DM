<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\ServicePrice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Smoke test HTTP untuk setiap halaman Phase 2. Menangkap error Blade,
 * route salah, atau data master yang belum di-seed.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pageProvider(): array
    {
        $pages = [
            '/dashboard',
            '/employees',
            '/employees/create',
            '/customers',
            '/customers/create',
            '/products',
            '/products/create',
            '/product-categories',
            '/product-categories/create',
            '/brands',
            '/brands/create',
            '/units',
            '/units/create',
            '/services',
            '/services/create',
            '/service-categories',
            '/service-categories/create',
            '/packages',
            '/packages/create',
            '/suppliers',
            '/suppliers/create',
        ];

        return array_combine($pages, array_map(fn ($p) => [$p], $pages));
    }

    #[Test]
    #[DataProvider('pageProvider')]
    public function page_renders_for_super_admin(string $url): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get($url)
            ->assertOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyStateProvider(): array
    {
        $pages = ['/employees', '/customers', '/products', '/services', '/packages', '/suppliers'];

        return array_combine($pages, array_map(fn ($p) => [$p], $pages));
    }

    #[Test]
    #[DataProvider('emptyStateProvider')]
    public function page_renders_with_empty_dataset(string $url): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get($url)
            ->assertOk()
            ->assertSee('Belum ada data', false);
    }

    #[Test]
    public function employee_detail_page_renders(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee($employee->full_name);
    }

    #[Test]
    public function customer_detail_page_renders(): void
    {
        $customer = \App\Models\Customer::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get("/customers/{$customer->id}")
            ->assertOk()
            ->assertSee($customer->customer_code);
    }

    #[Test]
    public function product_detail_and_price_page_render(): void
    {
        $product = Product::factory()->create();

        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get("/products/{$product->id}")->assertOk();
        $this->actingAs($user)->get("/products/{$product->id}/prices")->assertOk();
    }

    #[Test]
    public function service_detail_and_price_page_render(): void
    {
        $service = Service::factory()->weight()->create();
        ServicePrice::create([
            'service_id' => $service->id,
            'min_weight' => 40,
            'max_weight' => 50,
            'price' => 200000,
            'effective_date' => now()->toDateString(),
        ]);

        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get("/services/{$service->id}")->assertOk();
        $this->actingAs($user)->get("/services/{$service->id}/prices")->assertOk();
    }

    #[Test]
    public function package_detail_page_renders(): void
    {
        $package = ServicePackage::factory()->create();
        $package->services()->attach(Service::factory()->create()->id, ['quantity' => 2]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get("/packages/{$package->id}")
            ->assertOk()
            ->assertSee($package->code);
    }

    #[Test]
    public function supplier_detail_page_renders(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get("/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertSee($supplier->supplier_code);
    }

    #[Test]
    public function supplier_bank_account_page_renders_for_sensitive_permission(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get("/suppliers/{$supplier->id}/bank-accounts")->assertOk();
    }

    #[Test]
    public function edit_pages_render_with_existing_records(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get('/employees/'.Employee::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/customers/'.\App\Models\Customer::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/products/'.Product::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/services/'.Service::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/packages/'.ServicePackage::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/suppliers/'.Supplier::factory()->create()->id.'/edit')->assertOk();
    }

    #[Test]
    public function simple_master_edit_pages_render(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get('/product-categories/'.\App\Models\ProductCategory::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/brands/'.Brand::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/units/'.Unit::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($user)->get('/service-categories/'.\App\Models\ServiceCategory::factory()->create()->id.'/edit')->assertOk();
    }
}