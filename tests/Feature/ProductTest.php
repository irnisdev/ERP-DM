<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §9 — Product workflow: category, brand, unit, product, price.
 */
class ProductTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'SKU-MINYAK-01',
            'barcode' => '8991234567890',
            'category_id' => ProductCategory::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Minyak Massage 100ml',
            'purchase_price' => 100000,
            'default_sell_price' => 150000,
            'minimum_stock' => 5,
            'status' => 'ACTIVE',
        ], $overrides);
    }

    #[Test]
    public function store_creates_product(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'SKU-MINYAK-01', 'default_sell_price' => 150000]);
    }

    #[Test]
    public function duplicate_sku_is_rejected(): void
    {
        Product::factory()->create(['sku' => 'SKU-MINYAK-01']);

        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload())
            ->assertSessionHasErrors('sku');
    }

    #[Test]
    public function duplicate_barcode_is_rejected(): void
    {
        Product::factory()->create(['barcode' => '8991234567890']);

        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload())
            ->assertSessionHasErrors('barcode');
    }

    #[Test]
    public function barcode_is_optional(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload(['barcode' => null, 'sku' => 'SKU-NO-BARCODE']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'SKU-NO-BARCODE', 'barcode' => null]);
    }

    #[Test]
    public function maximum_stock_below_minimum_is_rejected(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload(['minimum_stock' => 20, 'maximum_stock' => 10]))
            ->assertSessionHasErrors('maximum_stock');
    }

    #[Test]
    public function negative_price_is_rejected(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload(['default_sell_price' => -1]))
            ->assertSessionHasErrors('default_sell_price');
    }

    #[Test]
    public function user_without_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/products')
            ->assertForbidden();
    }

    #[Test]
    public function price_history_can_have_multiple_entries(): void
    {
        $product = Product::factory()->create();
        $user = $this->userWith(['product.manage']);

        $this->actingAs($user)
            ->post("/products/{$product->id}/prices", [
                'price_type' => 'RETAIL',
                'price' => 150000,
                'effective_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post("/products/{$product->id}/prices", [
                'price_type' => 'RETAIL',
                'price' => 175000,
                'effective_date' => '2025-01-01',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $product->prices()->count());
    }

    #[Test]
    public function effective_price_respects_period(): void
    {
        $product = Product::factory()->create();

        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'RETAIL', 'price' => 150000,
            'effective_date' => '2024-01-01', 'end_date' => '2024-12-31',
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'RETAIL', 'price' => 175000,
            'effective_date' => '2025-01-01',
        ]);

        $this->assertEquals(150000.0, (float) $product->priceFor('RETAIL', '2024-06-01')->price);
        $this->assertEquals(175000.0, (float) $product->priceFor('RETAIL', '2025-06-01')->price);
    }

    #[Test]
    public function price_belongs_to_product_only(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $price = ProductPrice::create([
            'product_id' => $productA->id, 'price_type' => 'RETAIL',
            'price' => 100000, 'effective_date' => now()->toDateString(),
        ]);

        $user = $this->userWith(['product.manage']);

        // Hapus lewat produk yang salah harus ditolak
        $this->actingAs($user)
            ->delete("/products/{$productB->id}/prices/{$price->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('product_prices', ['id' => $price->id]);
    }

    #[Test]
    public function product_with_sales_history_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        \App\Models\SalesTransactionItem::create([
            'sales_transaction_id' => \App\Models\SalesTransaction::factory()->create()->id,
            'item_type' => \App\Models\SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 150000,
            'subtotal' => 300000,
        ]);

        $this->actingAs($this->userWith(['product.manage']))
            ->delete('/products/'.$product->id)
            ->assertForbidden();
    }

    #[Test]
    public function unsold_product_can_be_soft_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->userWith(['product.manage']))
            ->delete('/products/'.$product->id)
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function search_filters_listing(): void
    {
        $user = $this->userWith(['product.view']);

        Product::factory()->create(['name' => 'Minyak Kelapa', 'sku' => 'SKU-KELAPA']);
        Product::factory()->create(['name' => 'Krim Muka', 'sku' => 'SKU-KRIM']);

        $this->actingAs($user)
            ->get('/products?search=Kelapa')
            ->assertOk()
            ->assertSee('Minyak Kelapa')
            ->assertDontSee('Krim Muka');
    }

    #[Test]
    public function product_creation_is_audited(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/products', $this->payload());

        $product = Product::where('sku', 'SKU-MINYAK-01')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'action' => 'created',
        ]);
    }
}