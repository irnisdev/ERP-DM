<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Warehouse;
use App\Models\CommissionRule;
use App\Models\EmployeeCommission;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\SalesService;
use App\Services\ServicePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §12 POS workflow dan PRD 01 §30 transaction integrity.
 * Checkout harus atomik: tidak ada pembayaran tanpa invoice, tidak ada stok
 * berkurang tanpa transaksi.
 */
class SalesCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Warehouse $warehouse;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['branch_id' => $this->branch->id]);

        $this->paymentMethod = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Tunai',
            'method_type' => 'CASH',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Produk dengan stok tersedia.
     */
    private function product(float $stock = 50, float $sellPrice = 150000): Product
    {
        $product = Product::factory()->create([
            'default_sell_price' => $sellPrice,
            'purchase_price' => 100000,
        ]);

        $this->stockIn($product, $stock);

        return $product;
    }

    /**
     * Membuat stok lewat InventoryService agar ledger dan cache konsisten
     * sejak awal, seperti pembelian sungguhan.
     */
    private function stockIn(Product $product, float $quantity): void
    {
        app(InventoryService::class)->increase(
            warehouse: $this->warehouse,
            product: $product,
            quantity: $quantity,
            movementType: InventoryMovement::TYPE_PURCHASE,
            unitCost: (float) $product->purchase_price,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $payment
     * @return array{0: SalesTransaction, 1: \Illuminate\Support\Collection}
     */
    private function checkout(array $cart, array $context = [], ?array $payment = null): array
    {
        return app(SalesService::class)->checkout(
            $cart,
            array_merge([
                'branch_id' => $this->branch->id,
                'customer_id' => null,
                'created_by' => null,
            ], $context),
            $payment,
        );
    }

    // ---- Header & item

    #[Test]
    public function checkout_creates_transaction_and_items(): void
    {
        $product = $this->product();

        [$transaction] = $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 2,
        ]]);

        $this->assertSame($this->branch->id, $transaction->branch_id);
        $this->assertCount(1, $transaction->items);
        $this->assertSame(SalesTransaction::STATUS_DRAFT, $transaction->status);
        $this->assertEquals(300000.0, (float) $transaction->grand_total);
        $this->assertStringStartsWith('INV-', $transaction->invoice_number);
    }

    #[Test]
    public function product_service_and_package_share_one_engine(): void
    {
        $product = $this->product();
        $service = Service::factory()->create();
        $package = ServicePackage::factory()->create();

        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 180000,
            'effective_date' => now()->toDateString(),
        ]);

        [$transaction] = $this->checkout([
            ['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 1],
            ['item_type' => SalesTransactionItem::TYPE_SERVICE, 'service_id' => $service->id, 'quantity' => 1],
            ['item_type' => SalesTransactionItem::TYPE_PACKAGE, 'package_id' => $package->id, 'quantity' => 1],
        ]);

        $this->assertCount(3, $transaction->items);
        $this->assertEqualsCanonicalizing(
            ['PRODUCT', 'SERVICE', 'PACKAGE'],
            $transaction->items->pluck('item_type')->all()
        );
    }

    #[Test]
    public function empty_cart_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout([]);
    }

    #[Test]
    public function unknown_item_type_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout([['item_type' => 'UNKNOWN', 'quantity' => 1]]);
    }

    #[Test]
    public function inactive_item_is_rejected(): void
    {
        $product = $this->product();
        $product->update(['status' => 'INACTIVE']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 1,
        ]]);
    }

    #[Test]
    public function zero_quantity_is_rejected(): void
    {
        $product = $this->product();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 0,
        ]]);
    }

    // ---- Stock

    #[Test]
    public function selling_product_reduces_stock_and_writes_movement(): void
    {
        $product = $this->product(stock: 50);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 3,
        ]]);

        $stock = InventoryStock::where('product_id', $product->id)->first();
        $this->assertEquals(47.0, (float) $stock->quantity);

        $movement = InventoryMovement::where('product_id', $product->id)
            ->where('movement_type', InventoryMovement::TYPE_SALE)
            ->first();

        $this->assertNotNull($movement, 'Penjualan harus menulis ledger SALE');
        $this->assertEquals(-3.0, (float) $movement->quantity);
        $this->assertSame(SalesTransaction::class, $movement->reference_type);
    }

    #[Test]
    public function selling_service_does_not_touch_stock(): void
    {
        $service = Service::factory()->create();

        $movementsBefore = InventoryMovement::count();

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'quantity' => 1,
        ]]);

        $this->assertSame($movementsBefore, InventoryMovement::count());
    }

    #[Test]
    public function insufficient_stock_rolls_back_entire_transaction(): void
    {
        $productA = $this->product(stock: 5);
        $productB = $this->product(stock: 5);

        $transactionsBefore = SalesTransaction::count();
        $movementsBefore = InventoryMovement::count();

        try {
            $this->checkout([
                ['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $productA->id, 'quantity' => 2],
                ['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $productB->id, 'quantity' => 99],
            ]);
            $this->fail('Harus gagal karena stok tidak cukup');
        } catch (\Illuminate\Validation\ValidationException) {
            // expected
        }

        // Tidak boleh ada transaksi, movement, atau perubahan stok yang tertinggal
        $this->assertSame($transactionsBefore, SalesTransaction::count());
        $this->assertSame($movementsBefore, InventoryMovement::count());
        $this->assertEquals(5.0, (float) InventoryStock::where('product_id', $productA->id)->value('quantity'));
        $this->assertEquals(5.0, (float) InventoryStock::where('product_id', $productB->id)->value('quantity'));
    }

    #[Test]
    public function negative_stock_is_blocked_by_default(): void
    {
        $product = $this->product(stock: 2);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 5,
        ]]);
    }

    #[Test]
    public function negative_stock_allowed_with_special_permission(): void
    {
        $product = $this->product(stock: 2);

        $this->checkout(
            [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 5]],
            ['allow_negative_stock' => true]
        );

        $this->assertEquals(-3.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));
    }

    #[Test]
    public function stock_cache_matches_ledger_after_sale(): void
    {
        $product = $this->product(stock: 20);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 7,
        ]]);

        $ledger = (float) InventoryMovement::where('product_id', $product->id)->sum('quantity');
        $cache = (float) InventoryStock::where('product_id', $product->id)->value('quantity');

        $this->assertSame($ledger, $cache);
    }

    // ---- Payment

    #[Test]
    public function payment_is_recorded_and_marks_transaction_paid(): void
    {
        $product = $this->product();

        [$transaction, $payments] = $this->checkout(
            [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 2]],
            [],
            ['payment_method_id' => $this->paymentMethod->id, 'amount' => 300000],
        );

        $this->assertCount(1, $payments);
        $this->assertSame(Payment::SOURCE_SALES, $payments->first()->transaction_type);
        $this->assertSame($transaction->id, $payments->first()->transaction_id);
        $this->assertSame(SalesTransaction::STATUS_PAID, $transaction->fresh()->status);
    }

    #[Test]
    public function partial_payment_marks_transaction_partially_paid(): void
    {
        $product = $this->product();

        [$transaction] = $this->checkout(
            [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 2]],
            [],
            ['payment_method_id' => $this->paymentMethod->id, 'amount' => 100000],
        );

        $this->assertSame(SalesTransaction::STATUS_PARTIALLY_PAID, $transaction->fresh()->status);
    }

    #[Test]
    public function payment_amount_cannot_exceed_total(): void
    {
        $product = $this->product();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout(
            [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 1]],
            [],
            ['payment_method_id' => $this->paymentMethod->id, 'amount' => 999999],
        );
    }

    #[Test]
    public function unpaid_sales_stays_draft(): void
    {
        $product = $this->product();

        [$transaction] = $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 1,
        ]]);

        $this->assertSame(SalesTransaction::STATUS_DRAFT, $transaction->status);
        $this->assertSame(0, $transaction->payments()->count());
    }

    #[Test]
    public function payment_survives_when_stock_fails(): void
    {
        $product = $this->product(stock: 1);

        try {
            $this->checkout(
                [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 10]],
                [],
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 100000],
            );
        } catch (\Illuminate\Validation\ValidationException) {
            // expected
        }

        // Tidak boleh ada pembayaran yatim (PRD 01 §30)
        $this->assertSame(0, Payment::count());
    }

    // ---- Snapshot

    #[Test]
    public function transaction_stores_price_snapshot(): void
    {
        $product = Product::factory()->create(['default_sell_price' => 150000]);
        $this->stockIn($product, 100);

        [$transaction] = $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 1,
        ]]);

        $item = $transaction->items->first();

        // Harga master berubah setelah transaksi
        $product->update(['default_sell_price' => 999000]);
        $product->name = 'Nama Baru';

        $this->assertEquals(150000.0, (float) $item->fresh()->unit_price);
        $this->assertSame($item->item_name, $item->fresh()->item_name);
        $this->assertNotSame('Nama Baru', $item->fresh()->item_name);
    }

    // ---- Commission

    #[Test]
    public function service_sale_with_employee_creates_commission(): void
    {
        $service = Service::factory()->create();
        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 200000,
            'effective_date' => now()->toDateString(),
        ]);

        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);

        CommissionRule::create([
            'employee_id' => $therapist->id,
            'service_id' => $service->id,
            'commission_type' => 'PERCENTAGE',
            'commission_value' => 20,
            'effective_date' => now()->subMonth()->toDateString(),
            'status' => 'ACTIVE',
        ]);

        [$transaction] = $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'quantity' => 1,
            'employee_id' => $therapist->id,
        ]]);

        $commission = EmployeeCommission::where('employee_id', $therapist->id)->first();

        $this->assertNotNull($commission, 'Komisi harus dibuat untuk service dengan therapist');
        $this->assertEquals(40000.0, (float) $commission->commission_amount);
        $this->assertSame($transaction->id, $commission->sales_transaction_id);
        $this->assertSame(EmployeeCommission::STATUS_PENDING, $commission->status);
    }

    #[Test]
    public function no_commission_without_rule(): void
    {
        $service = Service::factory()->create();
        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 200000,
            'effective_date' => now()->toDateString(),
        ]);

        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'quantity' => 1,
            'employee_id' => $therapist->id,
        ]]);

        $this->assertSame(0, EmployeeCommission::count());
    }

    #[Test]
    public function product_sale_never_creates_commission(): void
    {
        $product = $this->product();
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 1,
            'employee_id' => $employee->id,
        ]]);

        $this->assertSame(0, EmployeeCommission::count());
    }

    #[Test]
    public function service_level_rule_applies_to_all_employees(): void
    {
        $service = Service::factory()->create();
        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 200000,
            'effective_date' => now()->toDateString(),
        ]);

        CommissionRule::create([
            'employee_id' => null,
            'service_id' => $service->id,
            'commission_type' => 'FIXED',
            'commission_value' => 25000,
            'effective_date' => now()->subMonth()->toDateString(),
            'status' => 'ACTIVE',
        ]);

        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_SERVICE,
            'service_id' => $service->id,
            'quantity' => 2,
            'employee_id' => $therapist->id,
        ]]);

        // FIXED: satu nilai per item, bukan per qty
        $this->assertEquals(25000.0, (float) EmployeeCommission::first()->commission_amount);
    }

    #[Test]
    public function employee_specific_rule_wins_over_service_rule(): void
    {
        $service = Service::factory()->create();
        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);

        CommissionRule::create([
            'employee_id' => null, 'service_id' => $service->id,
            'commission_type' => 'FIXED', 'commission_value' => 10000,
            'effective_date' => now()->subMonth()->toDateString(), 'status' => 'ACTIVE',
        ]);
        CommissionRule::create([
            'employee_id' => $therapist->id, 'service_id' => $service->id,
            'commission_type' => 'FIXED', 'commission_value' => 30000,
            'effective_date' => now()->subMonth()->toDateString(), 'status' => 'ACTIVE',
        ]);

        $rule = app(\App\Services\CommissionService::class)->resolveRule($therapist, $service);

        $this->assertSame(30000.0, (float) $rule->commission_value);
    }

    // ---- Cancel

    #[Test]
    public function cancel_restores_stock_and_voids_commission(): void
    {
        $product = $this->product(stock: 30);
        $service = Service::factory()->create();
        app(ServicePricingService::class)->addPrice($service, [
            'duration_minutes' => 60, 'price' => 200000,
            'effective_date' => now()->toDateString(),
        ]);

        $therapist = Employee::factory()->create(['branch_id' => $this->branch->id]);
        CommissionRule::create([
            'employee_id' => $therapist->id, 'service_id' => $service->id,
            'commission_type' => 'PERCENTAGE', 'commission_value' => 10,
            'effective_date' => now()->subMonth()->toDateString(), 'status' => 'ACTIVE',
        ]);

        [$transaction] = $this->checkout([
            ['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 4],
            ['item_type' => SalesTransactionItem::TYPE_SERVICE, 'service_id' => $service->id,
                'quantity' => 1, 'employee_id' => $therapist->id],
        ]);

        $this->assertEquals(26.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));

        app(SalesService::class)->cancel($transaction, 'Customer batal');

        $this->assertSame(30.0, (float) InventoryStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(SalesTransaction::STATUS_CANCELLED, $transaction->fresh()->status);
        $this->assertSame(
            EmployeeCommission::STATUS_VOID,
            EmployeeCommission::first()->fresh()->status
        );
        // Transaksi tidak boleh dihapus (PRD 02 §37.7)
        $this->assertDatabaseHas('sales_transactions', ['id' => $transaction->id]);
        $this->assertSame(1, SalesTransaction::query()->whereKey($transaction->id)->count());
    }

    #[Test]
    public function cancel_keeps_stock_consistent_with_ledger(): void
    {
        $product = $this->product(stock: 10);

        [$transaction] = $this->checkout([[
            'item_type' => SalesTransactionItem::TYPE_PRODUCT,
            'product_id' => $product->id,
            'quantity' => 4,
        ]]);

        app(SalesService::class)->cancel($transaction);

        $ledger = (float) InventoryMovement::where('product_id', $product->id)->sum('quantity');
        $cache = (float) InventoryStock::where('product_id', $product->id)->value('quantity');

        $this->assertSame($ledger, $cache);
        $this->assertSame(10.0, $ledger);
    }

    #[Test]
    public function checkout_requires_warehouse_for_product_sale(): void
    {
        $orphanBranch = Branch::factory()->create();
        $product = $this->product();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->checkout(
            [['item_type' => SalesTransactionItem::TYPE_PRODUCT, 'product_id' => $product->id, 'quantity' => 1]],
            ['branch_id' => $orphanBranch->id],
        );
    }
}