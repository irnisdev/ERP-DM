<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §7 — Employee workflow dan branch isolation.
 */
class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions, ?Branch $branch = null, string $role = 'ADMIN'): User
    {
        $user = User::factory()->create([
            'employee_id' => $branch
                ? Employee::factory()->create(['branch_id' => $branch->id])->id
                : null,
        ]);

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

    private function payload(Branch $branch, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Siti Nurhaliza',
            'gender' => 'F',
            'branch_id' => $branch->id,
            'employment_status' => 'ACTIVE',
            'status' => 'ACTIVE',
        ], $overrides);
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    #[Test]
    public function user_without_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/employees')
            ->assertForbidden();
    }

    #[Test]
    public function store_creates_employee_with_generated_code(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post('/employees', $this->payload($branch))
            ->assertSessionHasNoErrors();

        $employee = Employee::where('full_name', 'Siti Nurhaliza')->firstOrFail();

        $this->assertStringStartsWith('EMP-', $employee->employee_code);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'branch_id' => $branch->id]);
    }

    #[Test]
    public function store_requires_name_branch_and_status(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post('/employees', [])
            ->assertSessionHasErrors(['full_name', 'branch_id', 'employment_status']);

        // Hanya employee milik user test helper, tidak ada employee baru.
        $this->assertSame(1, Employee::count());
    }

    #[Test]
    public function duplicate_nik_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        Employee::factory()->create(['branch_id' => $branch->id, 'nik' => '327300000001']);

        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post('/employees', $this->payload($branch, ['nik' => '327300000001']))
            ->assertSessionHasErrors('nik');
    }

    #[Test]
    public function resign_date_before_join_date_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post('/employees', $this->payload($branch, [
                'join_date' => '2024-01-01',
                'resign_date' => '2023-01-01',
            ]))
            ->assertSessionHasErrors('resign_date');
    }

    #[Test]
    public function branch_user_cannot_create_employee_in_other_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $user = $this->userWith(['employee.manage'], $ownBranch);

        $this->actingAs($user)
            ->post('/employees', $this->payload($otherBranch))
            ->assertForbidden();

        $this->assertSame(0, Employee::where('branch_id', $otherBranch->id)->count());
    }

    #[Test]
    public function super_admin_can_create_employee_in_any_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post('/employees', $this->payload($otherBranch))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'full_name' => 'Siti Nurhaliza',
            'branch_id' => $otherBranch->id,
        ]);
    }

    #[Test]
    public function listing_is_limited_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        Employee::factory()->create([
            'branch_id' => $ownBranch->id,
            'full_name' => 'Milik Sendiri',
        ]);
        Employee::factory()->create([
            'branch_id' => $otherBranch->id,
            'full_name' => 'Milik Cabang Lain',
        ]);

        $user = $this->userWith(['employee.view'], $ownBranch);

        $this->actingAs($user)->get('/employees')
            ->assertOk()
            ->assertSee('Milik Sendiri')
            ->assertDontSee('Milik Cabang Lain');
    }

    #[Test]
    public function branch_user_cannot_view_employee_from_other_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

        $user = $this->userWith(['employee.view'], $ownBranch);

        $this->actingAs($user)->get('/employees/'.$employee->id)->assertForbidden();
    }

    #[Test]
    public function employee_cannot_be_own_manager(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->put('/employees/'.$employee->id, $this->payload($branch, ['manager_id' => $employee->id]))
            ->assertSessionHasErrors('manager_id');
    }

    #[Test]
    public function family_can_be_added_and_removed(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post("/employees/{$employee->id}/family", [
                'name' => 'Budi Santoso',
                'relationship' => 'Spouse',
                'is_dependent' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employee_family_members', [
            'employee_id' => $employee->id,
            'name' => 'Budi Santoso',
            'relationship' => 'Spouse',
        ]);

        $memberId = $employee->familyMembers()->value('id');

        $this->actingAs($user)
            ->delete("/employees/{$employee->id}/family/{$memberId}")
            ->assertRedirect();

        $this->assertDatabaseMissing('employee_family_members', ['id' => $memberId]);
    }

    #[Test]
    public function family_of_other_branch_employee_is_forbidden(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

        $user = $this->userWith(['employee.manage'], $ownBranch);

        $this->actingAs($user)
            ->post("/employees/{$employee->id}/family", [
                'name' => 'X',
                'relationship' => 'Child',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function bpjs_requires_sensitive_permission(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        // Hanya employee.manage, tanpa sensitive.bpjs
        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->post("/employees/{$employee->id}/insurances", [
                'insurance_type' => 'BPJS_KES',
                'membership_number' => '0001-2345',
                'start_date' => '2024-01-01',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function bpjs_can_be_added_with_sensitive_permission(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $user = $this->userWith(['employee.manage', 'sensitive.bpjs'], $branch);

        $this->actingAs($user)
            ->post("/employees/{$employee->id}/insurances", [
                'insurance_type' => 'BPJS_KES',
                'membership_number' => '0001-2345',
                'provider' => 'BPJS',
                'start_date' => '2024-01-01',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employee_insurances', [
            'employee_id' => $employee->id,
            'membership_number' => '0001-2345',
        ]);
    }

    #[Test]
    public function employee_with_history_cannot_be_deleted(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        \App\Models\Attendance::create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'PRESENT',
            'source' => 'MANUAL',
        ]);

        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->delete('/employees/'.$employee->id)
            ->assertForbidden();
    }

    #[Test]
    public function employee_without_history_can_be_soft_deleted(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)
            ->delete('/employees/'.$employee->id)
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    #[Test]
    public function employee_creation_is_audited(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWith(['employee.manage'], $branch);

        $this->actingAs($user)->post('/employees', $this->payload($branch));

        $employee = Employee::where('full_name', 'Siti Nurhaliza')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'action' => 'created',
        ]);
    }

    #[Test]
    public function employee_without_permission_cannot_add_family_even_on_own_branch(): void
    {
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        // Hanya employee.view
        $user = $this->userWith(['employee.view'], $branch);

        $this->actingAs($user)
            ->post("/employees/{$employee->id}/family", [
                'name' => 'X',
                'relationship' => 'Child',
            ])
            ->assertForbidden();
    }
}