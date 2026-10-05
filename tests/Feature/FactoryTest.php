<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Factory master data harus dapat dipakai test modul berikutnya (PRD 02 §33).
 */
class FactoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function master_factories_are_resolvable(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company, 'company')->create();
        $employee = Employee::factory()->for($branch, 'branch')->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $service = Service::factory()->create();

        $this->assertSame($company->id, $branch->company_id);
        $this->assertSame($branch->id, $employee->branch_id);
        $this->assertNotNull($customer->customer_code);
        $this->assertNotNull($product->sku);
        $this->assertNotNull($service->service_code);
    }

    #[Test]
    public function user_factory_can_assign_branch(): void
    {
        $branch = Branch::factory()->create();

        $user = User::factory()->forBranch($branch)->create();

        $this->assertNotNull($user->employee);
        $this->assertSame($branch->id, $user->employee->branch_id);
        $this->assertSame([$branch->id], $user->accessibleBranchIds());
    }

    #[Test]
    public function super_admin_can_access_all_branches(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertNull($user->accessibleBranchIds());
    }

    #[Test]
    public function service_factory_states_set_pricing_method(): void
    {
        $this->assertSame('DURATION', Service::factory()->duration()->create()->pricing_method);
        $this->assertSame('WEIGHT', Service::factory()->weight()->create()->pricing_method);
    }
}