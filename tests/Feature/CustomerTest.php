<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §8 — Customer workflow: create dari master/POS/booking,
 * warning duplicate sebelum membuat record baru.
 */
class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermission(string $permission, string $role = 'ADMIN'): User
    {
        $user = User::factory()->create();

        $roleModel = Role::firstOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['description' => $role]
        );

        $perm = \App\Models\Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['description' => $permission]
        );

        $roleModel->permissions()->syncWithoutDetaching([$perm->id]);
        $user->roles()->syncWithoutDetaching([$roleModel->id]);

        return $user->fresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'gender' => 'M',
            'phone' => '08123456789',
            'email' => 'budi@example.com',
            'customer_type' => 'RETAIL',
            'status' => 'ACTIVE',
        ], $overrides);
    }

    #[Test]
    public function guest_cannot_access_customer_page(): void
    {
        $this->get('/customers')->assertRedirect('/login');
    }

    #[Test]
    public function authorized_user_can_list_customers(): void
    {
        $user = $this->userWithPermission('customer.view');
        Customer::factory()->create(['name' => 'Budi Santoso']);

        $this->actingAs($user)->get('/customers')
            ->assertOk()
            ->assertViewIs('master.customer.index')
            ->assertSee('Budi Santoso', false);
    }

    #[Test]
    public function user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/customers')->assertForbidden();
    }

    #[Test]
    public function store_creates_customer_with_generated_code(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $response = $this->actingAs($user)->post('/customers', $this->payload());

        $customer = Customer::first();
        $this->assertNotNull($customer);
        $this->assertStringStartsWith('CUST-', $customer->customer_code);
        $response->assertRedirect(route('customers.show', $customer));
    }

    #[Test]
    public function store_rejects_missing_required_fields(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->post('/customers', [])
            ->assertSessionHasErrors(['name', 'customer_type']);

        $this->assertSame(0, Customer::count());
    }

    #[Test]
    public function store_rejects_future_birth_date(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->post('/customers', $this->payload(['birth_date' => now()->addYear()->toDateString()]))
            ->assertSessionHasErrors('birth_date');
    }

    #[Test]
    public function duplicate_phone_blocks_creation_with_warning(): void
    {
        Customer::factory()->create(['phone' => '08123456789', 'email' => 'lain@example.com']);

        $user = $this->userWithPermission('customer.manage');

        $response = $this->from('/customers/create')->actingAs($user)->post('/customers', $this->payload());

        $response->assertRedirect('/customers/create');
        $response->assertSessionHas('duplicate_warning');
        $this->assertSame(1, Customer::count(), 'Customer tidak boleh dibuat tanpa konfirmasi');
    }

    #[Test]
    public function duplicate_email_also_triggers_warning(): void
    {
        Customer::factory()->create(['phone' => '08999999999', 'email' => 'budi@example.com']);

        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->post('/customers', $this->payload())
            ->assertSessionHas('duplicate_warning');
    }

    #[Test]
    public function force_create_allows_duplicate_creation(): void
    {
        Customer::factory()->create(['phone' => '08123456789', 'email' => 'lain@example.com']);

        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->post('/customers', $this->payload(['force_create' => true]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Customer::count());
    }

    #[Test]
    public function check_duplicates_endpoint_returns_matches(): void
    {
        $existing = Customer::factory()->create([
            'phone' => '08123456789',
            'email' => 'budi@example.com',
        ]);

        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->getJson('/customers/lookup/duplicates?phone=08123456789')
            ->assertOk()
            ->assertJsonPath('has_duplicates', true)
            ->assertJsonPath('matches.0.id', $existing->id);
    }

    #[Test]
    public function check_duplicates_returns_empty_when_no_match(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->getJson('/customers/lookup/duplicates?phone=089000000000')
            ->assertOk()
            ->assertJsonPath('has_duplicates', false)
            ->assertJsonCount(0, 'matches');
    }

    #[Test]
    public function search_filters_listing(): void
    {
        $user = $this->userWithPermission('customer.view');

        Customer::factory()->create(['name' => 'Andi', 'phone' => '08111111111']);
        Customer::factory()->create(['name' => 'Siti', 'phone' => '08222222222']);

        $this->actingAs($user)
            ->get('/customers?search=Andi')
            ->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Siti');
    }

    #[Test]
    public function update_modifies_existing_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Nama Lama']);

        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)
            ->put('/customers/'.$customer->id, $this->payload(['name' => 'Nama Baru']))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Nama Baru', $customer->fresh()->name);
    }

    #[Test]
    public function destroy_soft_deletes_customer(): void
    {
        $customer = Customer::factory()->create();

        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)->delete('/customers/'.$customer->id);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    #[Test]
    public function customer_is_audited_on_creation(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)->post('/customers', $this->payload());

        $customer = Customer::first();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'action' => 'created',
        ]);
    }

    #[Test]
    public function generated_code_is_unique_even_with_same_name(): void
    {
        $user = $this->userWithPermission('customer.manage');

        $this->actingAs($user)->post('/customers', $this->payload([
            'phone' => '08111111111', 'email' => 'a@example.com', 'force_create' => true,
        ]));
        $this->actingAs($user)->post('/customers', $this->payload([
            'phone' => '08222222222', 'email' => 'b@example.com', 'force_create' => true,
        ]));

        $codes = Customer::pluck('customer_code');

        $this->assertCount(2, $codes);
        $this->assertEquals($codes->count(), $codes->unique()->count(), 'Kode customer harus unik');
    }
}