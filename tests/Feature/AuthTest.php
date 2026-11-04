<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Buat user operator dengan password spesifik
        User::create([
            'username' => 'operator',
            'password' => Hash::make('SecretPassword123!'),
            'role' => 'Operator',
        ]);
    }

    /**
     * Test login dengan password umum / salah ditolak.
     */
    public function test_login_with_invalid_or_common_password_is_rejected(): void
    {
        $response = $this->post('/login', [
            'username' => 'operator',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $response2 = $this->post('/login', [
            'username' => 'operator',
            'password' => '123456',
        ]);

        $response2->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test route quick-login mengembalikan 404 Not Found.
     */
    public function test_quick_login_route_returns_404(): void
    {
        $response = $this->get('/quick-login/operator');
        $response->assertStatus(404);

        $responseWali = $this->get('/quick-login/walikelas');
        $responseWali->assertStatus(404);
    }

    /**
     * Test throttle rate limiting: percobaan ke-6 mengembalikan status 429 Too Many Requests.
     */
    public function test_login_throttle_blocks_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'username' => 'operator',
                'password' => 'WrongPassword' . $i,
            ]);
            $response->assertSessionHasErrors('username');
        }

        // Percobaan ke-6 harus di-throttle (HTTP 429)
        $response = $this->post('/login', [
            'username' => 'operator',
            'password' => 'WrongPassword6',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test logout via GET ditolak dan logout via POST berhasil.
     */
    public function test_logout_get_method_is_disallowed_and_post_succeeds(): void
    {
        $operator = User::where('username', 'operator')->first();

        // GET /logout harus ditolak (Method Not Allowed / 405)
        $responseGet = $this->actingAs($operator)->get('/logout');
        $responseGet->assertStatus(405);

        // POST /logout harus berhasil logout
        $responsePost = $this->actingAs($operator)->post('/logout');
        $responsePost->assertRedirect('/login');
        $this->assertGuest();
    }
}
