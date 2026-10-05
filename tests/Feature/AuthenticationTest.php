<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §28 — password hashing, CSRF, rate limiting login, session security.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ], $overrides));
    }

    #[Test]
    public function login_page_is_guest_only(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    #[Test]
    public function valid_credentials_log_in(): void
    {
        $user = $this->user();

        $response = $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    #[Test]
    public function invalid_password_is_rejected(): void
    {
        $this->user();

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function password_is_hashed_not_stored_plaintext(): void
    {
        $user = $this->user();

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    #[Test]
    public function inactive_account_cannot_log_in(): void
    {
        $this->user(['status' => 'INACTIVE']);

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function login_is_rate_limited(): void
    {
        $this->user();

        // 5 percobaan gagal, percobaan keenam diblokir
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'admin@dianmustika.co.id',
                'password' => 'salah'.$i,
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        RateLimiter::clear('login:admin@dianmustika.co.id|127.0.0.1');
    }

    #[Test]
    public function login_and_failure_are_audited(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'salah',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'login_failed',
        ]);

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'login',
        ]);
    }

    #[Test]
    public function session_is_regenerated_after_login(): void
    {
        $this->user();

        $before = session()->getId();

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ]);

        $this->assertNotSame($before, session()->getId(), 'Session ID harus di-regenerate untuk mitigasi session fixation');
    }

    #[Test]
    public function logout_clears_authentication(): void
    {
        $this->user();

        $this->post('/login', [
            'email' => 'admin@dianmustika.co.id',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    #[Test]
    public function guest_cannot_access_protected_pages(): void
    {
        $protected = [
            '/dashboard',
            '/employees',
            '/customers',
            '/products',
            '/services',
            '/packages',
            '/suppliers',
            '/brands',
            '/units',
            '/product-categories',
            '/service-categories',
        ];

        foreach ($protected as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    #[Test]
    public function root_redirects_to_login_or_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');

        Auth::loginUsingId($this->user()->id);

        $this->get('/')->assertRedirect('/dashboard');
    }

    #[Test]
    public function super_admin_reaches_all_modules(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $urls = [
            '/dashboard',
            '/employees',
            '/customers',
            '/products',
            '/services',
            '/packages',
            '/suppliers',
            '/brands',
            '/units',
            '/product-categories',
            '/service-categories',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }
}