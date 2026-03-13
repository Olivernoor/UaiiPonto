<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste: Registrar um usuário válido com sucesso
     */
    public function test_register_with_valid_data(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Tech Company',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Usuário registrado com sucesso',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Tech Company',
        ]);
    }

    /**
     * Teste: Não é possível registrar com e-mail já existente
     */
    public function test_register_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'joao@example.com',
            'organization' => 'Old Company',
        ]);

        $response = $this->postJson('/register', [
            'name' => 'Outro João',
            'email' => 'joao@example.com',
            'organization' => 'New Company',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /**
     * Teste: Não é possível registrar com organização já existente
     */
    public function test_register_with_existing_organization(): void
    {
        User::factory()->create([
            'organization' => 'Tech Company',
        ]);

        $response = $this->postJson('/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Tech Company',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('organization');
    }

    /**
     * Teste: Não é possível registrar com campos obrigatórios vazios
     */
    public function test_register_with_missing_required_fields(): void
    {
        $response = $this->postJson('/register', [
            'name' => '',
            'email' => '',
            'organization' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'organization', 'password']);
    }

    /**
     * Teste: Senha deve ter mínimo 6 caracteres
     */
    public function test_register_with_short_password(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Tech Company',
            'password' => 'Pass1!',
            'password_confirmation' => 'Pass1!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    /**
     * Teste: E-mail deve ter formato válido
     */
    public function test_register_with_invalid_email(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'João Silva',
            'email' => 'email-invalido',
            'organization' => 'Tech Company',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /**
     * Teste: Senha deve ser criptografada antes de salvar
     */
    public function test_password_is_encrypted(): void
    {
        $this->postJson('/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Tech Company',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $user = User::where('email', 'joao@example.com')->first();
        
        $this->assertNotEquals($user->password, 'Password@123');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Password@123', $user->password));
    }

    /**
     * Teste: Login com credenciais válidas
     */
    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'joao@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login realizado com sucesso',
        ]);
        $response->assertJsonStructure(['user', 'token']);
    }

    /**
     * Teste: Login com senha inválida
     */
    public function test_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'joao@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'joao@example.com',
            'password' => 'WrongPassword@123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'E-mail ou senha inválidos',
        ]);
    }

    /**
     * Teste: Logout com sucesso
     */
    public function test_logout_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Teste: Obter dados do usuário logado
     */
    public function test_get_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => 'João Silva',
                'email' => 'joao@example.com',
            ],
        ]);
    }

    /**
     * Teste: Atualizar dados do usuário
     */
    public function test_update_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'organization' => 'Old Company',
        ]);

        $response = $this->actingAs($user)->putJson("/users/{$user->id}", [
            'name' => 'João Silva Atualizado',
            'email' => 'joao.novo@example.com',
            'organization' => 'New Company',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'João Silva Atualizado',
            'email' => 'joao.novo@example.com',
            'organization' => 'New Company',
        ]);
    }

    /**
     * Teste: Não é possível atualizar usuário sem autenticação
     */
    public function test_cannot_update_without_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->putJson("/users/{$user->id}", [
            'name' => 'João Silva Atualizado',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Teste: Não é possível editar usuário de outra pessoa
     */
    public function test_cannot_update_other_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($user1)->putJson("/users/{$user2->id}", [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Não autorizado a editar este usuário',
        ]);
    }
}
