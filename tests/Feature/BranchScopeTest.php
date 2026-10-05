<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §6 — Owner/Super Admin melihat semua cabang, branch user hanya
 * melihat branch yang diizinkan.
 */
class BranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userInBranch(Branch $branch, string $role = 'ADMIN'): User
    {
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $user = User::factory()->create(['employee_id' => $employee->id]);
        $this->assignRole($user, $role);

        return $user->fresh();
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['description' => $roleName]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    #[Test]
    public function super_admin_may_access_all_branches(): void
    {
        $user = User::factory()->superAdmin()->create();
        $scope = app(BranchScope::class);

        $this->assertNull($scope->allowed($user));
        $this->assertTrue($scope->canAccess($user, 999));
        $this->assertTrue($scope->canAccess($user, null));
    }

    #[Test]
    public function branch_user_is_limited_to_own_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $user = $this->userInBranch($branchA);
        $scope = app(BranchScope::class);

        $this->assertSame([$branchA->id], $scope->allowed($user));
        $this->assertTrue($scope->canAccess($user, $branchA->id));
        $this->assertFalse($scope->canAccess($user, $branchB->id));
    }

    #[Test]
    public function scope_filters_query_to_allowed_branches(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Employee::factory()->count(2)->create(['branch_id' => $branchA->id]);
        Employee::factory()->count(3)->create(['branch_id' => $branchB->id]);

        $user = $this->userInBranch($branchA); // menambah 1 employee di branchA

        $visible = app(BranchScope::class)->apply($user, Employee::query());

        $this->assertSame(3, $visible->count());
        $this->assertSame(
            [$branchA->id],
            $visible->pluck('branch_id')->unique()->values()->all()
        );
    }

    #[Test]
    public function super_admin_scope_does_not_filter(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Employee::factory()->create(['branch_id' => $branchA->id]);
        Employee::factory()->create(['branch_id' => $branchB->id]);

        $user = User::factory()->superAdmin()->create();

        $this->assertSame(2, app(BranchScope::class)->apply($user, Employee::query())->count());
    }

    #[Test]
    public function user_without_employee_sees_nothing(): void
    {
        $user = User::factory()->create(['employee_id' => null]);
        $scope = app(BranchScope::class);

        $this->assertSame([], $scope->allowed($user));
        $this->assertSame(0, $scope->apply($user, Employee::query())->count());
        $this->assertFalse($scope->canAccess($user, 1));
    }

    #[Test]
    public function describe_returns_branch_name_or_all(): void
    {
        $branch = Branch::factory()->create(['name' => 'Klinik Bandung']);
        $user = $this->userInBranch($branch);

        $this->assertSame('Klinik Bandung', app(BranchScope::class)->describe($user));

        $superAdmin = User::factory()->superAdmin()->create();
        $this->assertSame('Semua cabang', app(BranchScope::class)->describe($superAdmin));
    }
}