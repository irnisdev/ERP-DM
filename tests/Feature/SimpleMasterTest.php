<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Master sederhana: kategori produk, brand, unit, kategori service.
 * Satu SimpleMasterController dengan empat subclass.
 */
class SimpleMasterTest extends TestCase
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

    #[Test]
    public function brand_index_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/brands')->assertForbidden();

        $this->actingAs($this->userWith(['product.view']))
            ->get('/brands')
            ->assertOk()
            ->assertViewIs('master.simple-master.index');
    }

    #[Test]
    public function brand_can_be_created(): void
    {
        $user = $this->userWith(['product.manage']);

        $this->actingAs($user)
            ->post('/brands', ['code' => 'DM', 'name' => 'Dian Mustika', 'status' => 'ACTIVE'])
            ->assertRedirect(route('brands.index'));

        $this->assertDatabaseHas('brands', ['code' => 'DM', 'name' => 'Dian Mustika']);
    }

    #[Test]
    public function duplicate_code_is_rejected(): void
    {
        Brand::factory()->create(['code' => 'DM']);

        $this->actingAs($this->userWith(['product.manage']))
            ->post('/brands', ['code' => 'DM', 'name' => 'Duplikat', 'status' => 'ACTIVE'])
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function brand_can_be_updated_and_deleted(): void
    {
        $brand = Brand::factory()->create(['name' => 'Lama']);

        $user = $this->userWith(['product.manage']);

        $this->actingAs($user)
            ->put('/brands/'.$brand->id, ['code' => $brand->code, 'name' => 'Baru', 'status' => 'ACTIVE'])
            ->assertRedirect(route('brands.index'));

        $this->assertSame('Baru', $brand->fresh()->name);

        $this->actingAs($user)->delete('/brands/'.$brand->id);
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    #[Test]
    public function product_category_in_use_cannot_be_deleted(): void
    {
        $category = \App\Models\ProductCategory::factory()->create();
        \App\Models\Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->userWith(['product.manage']))
            ->delete('/product-categories/'.$category->id)
            ->assertForbidden();
    }

    #[Test]
    public function unused_product_category_can_be_deleted(): void
    {
        $category = \App\Models\ProductCategory::factory()->create();

        $this->actingAs($this->userWith(['product.manage']))
            ->delete('/product-categories/'.$category->id)
            ->assertRedirect(route('product-categories.index'));

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    #[Test]
    public function service_category_uses_service_module_permission(): void
    {
        $category = \App\Models\ServiceCategory::factory()->create();

        // Hanya product.manage, tidak boleh manage service
        $user = $this->userWith(['product.manage']);
        $this->actingAs($user)
            ->put('/service-categories/'.$category->id, [
                'code' => $category->code, 'name' => 'X', 'status' => 'ACTIVE',
            ])
            ->assertForbidden();

        $serviceUser = $this->userWith(['service.manage']);
        $this->actingAs($serviceUser)
            ->put('/service-categories/'.$category->id, [
                'code' => $category->code, 'name' => 'Massage Therapy', 'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('service-categories.index'));

        $this->assertSame('Massage Therapy', $category->fresh()->name);
    }

    #[Test]
    public function unit_accepts_decimal_scale(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/units', [
                'code' => 'KG',
                'name' => 'Kilogram',
                'decimal_scale' => 3,
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('units', ['code' => 'KG', 'decimal_scale' => 3]);
    }

    #[Test]
    public function unit_rejects_invalid_decimal_scale(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/units', [
                'code' => 'XX', 'name' => 'Salah', 'decimal_scale' => 99, 'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('decimal_scale');
    }

    #[Test]
    public function category_can_have_parent(): void
    {
        $parent = \App\Models\ProductCategory::factory()->create();
        $child = \App\Models\ProductCategory::factory()->create(['parent_id' => $parent->id]);

        $this->assertSame($parent->id, $child->fresh()->parent_id);
        $this->assertTrue($parent->fresh()->children->contains($child));
    }

    #[Test]
    public function master_change_is_audited(): void
    {
        $this->actingAs($this->userWith(['product.manage']))
            ->post('/brands', ['code' => 'DM', 'name' => 'Dian Mustika', 'status' => 'ACTIVE']);

        $brand = Brand::where('code', 'DM')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Brand::class,
            'auditable_id' => $brand->id,
            'action' => 'created',
        ]);
    }
}