<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ServicePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Layer HTTP untuk Phase 3: POS, penjualan, pembayaran, booking, komisi.
 * Seluruh aturan bisnis diuji di service test; di sini yang diperiksa adalah
 * wiring route → policy → form request → service.
 */
class SalesAndBookingHttpTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Warehouse $warehouse;

    private PaymentMethod $cash;

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions, ?Branch $branch = null): User
    {
        $branch ??= $this->branch;

        $user = User::factory()->create([
            'employee_id' => Employee::factory()->create(['branch_id' => $branch->id])->id,
        ]);

        $role = Role::firstOrCreate(['name' => 'STAFF', 'guard_name' => 'web'], ['description' => 'STAFF']);
        $ids = [];

        foreach ($permissions as $name) {
            $ids[] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $name]
            )->id;
        }

        $role->permissions()->syncWithoutDetaching($ids);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['branch_id' => $this->branch->id]);

        $this->cash = PaymentMethod::create([
            'code' => 'CASH', 'name' => 'Tunai',
            'method_type' => 'CASH', 'status' => 'ACTIVE',
        ]);
    }

    private function product(float $stock = 50): Product
    {
        $product = Product::factory()->create([
            'purchase_price' => 100000,
            'default_sell_price' => 150000,
        ]);

        app(InventoryService::class)->increase(
            warehouse: $this->warehouse,
            product: $product,
            quantity: $stock,
            movementType: InventoryMovement::TYPE_PURCHASE,
        );

        return $product;
    }

    // ---------------- POS

    #[Test]
    public function pos_page_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/pos')->assertForbidden();
    }

    #[Test]
    public function pos_page_renders(): void
    {
        $this->actingAs($this->userWith(['sales.create', 'sales.view']))
            ->get('/pos')
            ->assertOk()
            ->assertViewIs('sales.pos.index');
    }

    #[Test]
    public function pos_catalog_returns_products_services_and_packages(): void
    {
        $this->product();

        $service = Service::factory()->create();
        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 180000,
            'effective_date' => now()->toDateString(),
        ]);

        ServicePackage::factory()->create();

        $response = $this->actingAs($this->userWith(['sales.create']))
            ->getJson('/pos/catalog?warehouse_id='.$this->warehouse->id)
            ->assertOk();

        $response->assertJsonStructure([
            'products' => [['item_type', 'id', 'name', 'price', 'stock']],
            'services' => [['item_type', 'id', 'name', 'price']],
            'packages' => [['item_type', 'id', 'name', 'price']],
        ]);

        $this->assertSame('SERVICE', $response->json('services.0.item_type'));
        $this->assertSame(50, (int) $response->json('products.0.stock'));
    }

    #[Test]
    public function pos_checkout_creates_transaction_with_payment(): void
    {
        $product = $this->product();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->userWith(['sales.create']))->post('/pos', [
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'cart' => [[
                'item_type' => 'PRODUCT',
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
            'payment' => [
                'payment_method_id' => $this->cash->id,
                'amount' => 300000,
            ],
        ]);

        $transaction = SalesTransaction::firstOrFail();

        $response->assertRedirect(route('sales.show', $transaction));
        $this->assertSame(SalesTransaction::STATUS_PAID, $transaction->status);
        $this->assertSame(1, Payment::count());
        $this->assertEquals(48.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));
    }

    #[Test]
    public function pos_rejects_empty_cart(): void
    {
        $this->actingAs($this->userWith(['sales.create']))
            ->post('/pos', ['branch_id' => $this->branch->id, 'cart' => []])
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, SalesTransaction::count());
    }

    #[Test]
    public function pos_rejects_item_type_without_matching_reference(): void
    {
        $this->actingAs($this->userWith(['sales.create']))
            ->post('/pos', [
                'branch_id' => $this->branch->id,
                'cart' => [['item_type' => 'PRODUCT', 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('cart.0.product_id');
    }

    #[Test]
    public function pos_rejects_payment_above_total(): void
    {
        $product = $this->product();

        $this->actingAs($this->userWith(['sales.create']))
            ->post('/pos', [
                'branch_id' => $this->branch->id,
                'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 1]],
                'payment' => ['payment_method_id' => $this->cash->id, 'amount' => 999999],
            ])
            ->assertSessionHasErrors('payment.amount');
    }

    #[Test]
    public function negative_stock_flag_requires_permission(): void
    {
        $product = $this->product(stock: 1);
        $user = $this->userWith(['sales.create']);

        $this->actingAs($user)
            ->post('/pos', [
                'branch_id' => $this->branch->id,
                'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 5]],
                'allow_negative_stock' => 1,
            ])
            ->assertSessionHasErrors('allow_negative_stock');

        $withPermission = $this->userWith(['sales.create', 'inventory.negative_stock']);

        $this->actingAs($withPermission)
            ->post('/pos', [
                'branch_id' => $this->branch->id,
                'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 5]],
                'allow_negative_stock' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(-4.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));
    }

    // ---------------- Sales listing & detail

    #[Test]
    public function sales_index_lists_transactions(): void
    {
        $product = $this->product();
        $user = $this->userWith(['sales.create', 'sales.view']);

        $this->actingAs($user)->post('/pos', [
            'branch_id' => $this->branch->id,
            'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->actingAs($user)->get('/sales')
            ->assertOk()
            ->assertViewIs('sales.index')
            ->assertSee(SalesTransaction::first()->invoice_number);
    }

    #[Test]
    public function sales_index_only_shows_own_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $otherWarehouse = Warehouse::factory()->create(['branch_id' => $otherBranch->id]);

        $product = Product::factory()->create(['default_sell_price' => 100000]);
        app(InventoryService::class)->increase(
            warehouse: $otherWarehouse, product: $product, quantity: 10,
            movementType: InventoryMovement::TYPE_PURCHASE,
        );

        $this->actingAs(User::factory()->superAdmin()->create())->post('/pos', [
            'branch_id' => $otherBranch->id,
            'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 1]],
        ]);

        $invoice = SalesTransaction::firstOrFail()->invoice_number;

        // User cabang sendiri tidak boleh bertransaksi di cabang lain
        $this->actingAs($user)->post('/pos', [
            'branch_id' => $otherBranch->id,
            'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 1]],
        ])->assertForbidden();

        // Dan tidak melihat transaksi cabang lain
        $this->actingAs($user)->get('/sales')->assertOk()->assertDontSee($invoice);

        // Super admin melihat semuanya
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/sales')->assertOk()->assertSee($invoice);
    }

    #[Test]
    public function branch_user_cannot_view_other_branch_transaction(): void
    {
        $otherBranch = Branch::factory()->create();
        $transaction = SalesTransaction::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($this->userWith(['sales.view']))
            ->get('/sales/'.$transaction->id)
            ->assertForbidden();
    }

    // ---------------- Payment

    #[Test]
    public function recording_payment_updates_status_and_outstanding(): void
    {
        $product = $this->product();
        $user = $this->userWith(['sales.create', 'sales.view', 'payment.manage']);

        $this->actingAs($user)->post('/pos', [
            'branch_id' => $this->branch->id,
            'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 2]],
        ]);

        $transaction = SalesTransaction::firstOrFail();
        $this->assertSame(SalesTransaction::STATUS_DRAFT, $transaction->status);

        $this->actingAs($user)
            ->post("/sales/{$transaction->id}/pay", [
                'payment_method_id' => $this->cash->id,
                'amount' => 300000,
            ])
            ->assertRedirect();

        $this->assertSame(SalesTransaction::STATUS_PAID, $transaction->fresh()->status);
        $this->assertSame(1, Payment::where('transaction_id', $transaction->id)->count());
    }

    #[Test]
    public function payment_requires_permission(): void
    {
        $transaction = SalesTransaction::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->userWith(['sales.view']))
            ->post("/sales/{$transaction->id}/pay", [
                'payment_method_id' => $this->cash->id,
                'amount' => 1000,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function payment_numbers_are_unique(): void
    {
        $user = $this->userWith(['sales.create', 'payment.manage']);

        foreach (range(1, 3) as $i) {
            $transaction = SalesTransaction::factory()->create([
                'branch_id' => $this->branch->id,
                'grand_total' => 100000 * $i,
            ]);

            $this->actingAs($user)->post("/sales/{$transaction->id}/pay", [
                'payment_method_id' => $this->cash->id,
                'amount' => 100000,
            ]);
        }

        $numbers = Payment::pluck('payment_number');
        $this->assertCount(3, $numbers);
        $this->assertCount(3, $numbers->unique());
    }

    // ---------------- Cancel

    #[Test]
    public function cancel_restores_stock_via_http(): void
    {
        $product = $this->product(stock: 20);
        $user = $this->userWith(['sales.create', 'sales.return']);

        $this->actingAs($user)->post('/pos', [
            'branch_id' => $this->branch->id,
            'cart' => [['item_type' => 'PRODUCT', 'product_id' => $product->id, 'quantity' => 5]],
        ]);

        $transaction = SalesTransaction::firstOrFail();
        $this->assertEquals(15.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));

        $this->actingAs($user)
            ->post("/sales/{$transaction->id}/cancel", ['reason' => 'Salah input'])
            ->assertRedirect();

        $this->assertEquals(20.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(SalesTransaction::STATUS_CANCELLED, $transaction->fresh()->status);
    }

    #[Test]
    public function cancel_requires_permission(): void
    {
        $transaction = SalesTransaction::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->userWith(['sales.view']))
            ->post("/sales/{$transaction->id}/cancel")
            ->assertForbidden();
    }

    // ---------------- Booking

    #[Test]
    public function booking_can_be_created(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $user = $this->userWith(['booking.manage', 'booking.view']);

        $response = $this->actingAs($user)->post('/bookings', [
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'booking_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'items' => [[
                'service_id' => $service->id,
                'employee_id' => $therapist->id,
            ]],
        ]);

        $booking = Booking::firstOrFail();

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertCount(1, $booking->items);
        $this->assertSame(BookingItem::STATUS_BOOKED, $booking->items->first()->status);
    }

    #[Test]
    public function booking_requires_at_least_one_item(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->userWith(['booking.manage']))
            ->post('/bookings', [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'booking_date' => now()->toDateString(),
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    }

    #[Test]
    public function booking_item_needs_service_or_package(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->userWith(['booking.manage']))
            ->post('/bookings', [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'booking_date' => now()->toDateString(),
                'items' => [['employee_id' => null]],
            ])
            ->assertSessionHasErrors('items.0.service_id');
    }

    #[Test]
    public function booking_rejects_overlapping_therapist_schedule(): void
    {
        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $date = now()->addDay()->toDateString();

        $user = $this->userWith(['booking.manage']);

        $this->actingAs($user)->post('/bookings', [
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'booking_date' => $date,
            'items' => [[
                'service_id' => $service->id,
                'employee_id' => $therapist->id,
                'scheduled_start' => $date.' 10:00:00',
                'scheduled_end' => $date.' 11:00:00',
            ]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/bookings', [
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'booking_date' => $date,
            'items' => [[
                'service_id' => Service::factory()->create()->id,
                'employee_id' => $therapist->id,
                'scheduled_start' => $date.' 10:30:00',
                'scheduled_end' => $date.' 11:30:00',
            ]],
        ])->assertSessionHasErrors('items.0.scheduled_start');
    }

    #[Test]
    public function booking_status_transitions_over_http(): void
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_BOOKED,
        ]);

        $user = $this->userWith(['booking.manage', 'booking.view']);

        foreach ([Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_IN] as $status) {
            $this->actingAs($user)
                ->post("/bookings/{$booking->id}/transition", ['status' => $status])
                ->assertRedirect();

            $this->assertSame($status, $booking->fresh()->status);
        }
    }

    #[Test]
    public function invalid_transition_is_rejected(): void
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_BOOKED,
        ]);

        $this->actingAs($this->userWith(['booking.manage']))
            ->post("/bookings/{$booking->id}/transition", ['status' => Booking::STATUS_COMPLETED])
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function booking_listing_is_limited_to_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        Booking::factory()->create(['branch_id' => $otherBranch->id, 'booking_number' => 'BKG-LAIN']);

        $this->actingAs($this->userWith(['booking.view']))
            ->get('/bookings')
            ->assertOk()
            ->assertDontSee('BKG-LAIN');
    }

    // ---------------- Commission visibility

    #[Test]
    public function therapist_only_sees_own_commission(): void
    {
        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);
        $other = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $transaction = SalesTransaction::factory()->create(['branch_id' => $this->branch->id]);

        EmployeeCommission::create([
            'employee_id' => $therapist->id,
            'sales_transaction_id' => $transaction->id,
            'commission_amount' => 40000,
            'status' => EmployeeCommission::STATUS_PENDING,
        ]);
        EmployeeCommission::create([
            'employee_id' => $other->id,
            'sales_transaction_id' => $transaction->id,
            'commission_amount' => 90000,
            'status' => EmployeeCommission::STATUS_PENDING,
        ]);

        $therapistUser = User::factory()->create(['employee_id' => $therapist->id]);
        $therapistRole = Role::firstOrCreate(['name' => 'THERAPIST', 'guard_name' => 'web'], ['description' => 'T']);
        $therapistRole->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(['name' => 'commission.view', 'guard_name' => 'web'], ['description' => 'x'])->id,
        ]);
        $therapistUser->roles()->syncWithoutDetaching([$therapistRole->id]);

        $therapistUser = $therapistUser->fresh();

        $this->actingAs($therapistUser)->get('/commissions')
            ->assertOk()
            ->assertSee('40.000')
            ->assertDontSee('90.000');
    }

    #[Test]
    public function super_admin_sees_all_commissions(): void
    {
        $employee = Employee::factory()->create();
        $transaction = SalesTransaction::factory()->create();

        EmployeeCommission::create([
            'employee_id' => $employee->id,
            'sales_transaction_id' => $transaction->id,
            'commission_amount' => 123456,
            'status' => EmployeeCommission::STATUS_PENDING,
        ]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/commissions')
            ->assertOk()
            ->assertSee('123.456');
    }
}
