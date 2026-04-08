<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Registrar um novo usuário
     * POST /api/register
     */
    public function register(StoreUserRequest $request)
    {
        // Validação é feita automaticamente pelo StoreUserRequest
        $validated = $request->validated();

        // Criptografar a senha
        $validated['password'] = Hash::make($validated['password']);

        // Criar o usuário
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuário registrado com sucesso',
            'user' => $user->only(['id', 'name', 'email', 'organization']),
        ], 201);
    }

    /**
     * Login de usuário
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user = $user->only(['id', 'name', 'email', 'organization', 'role']);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => $user,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'E-mail ou senha inválidos',
        ], 401);
    }

    /**
     * Logout de usuário
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ], 200);
    }

    /**
     * Atualizar dados do usuário
     * PUT/PATCH /api/users/{id}
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Verificar autenticação
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Não autenticado',
            ], 401);
        }

        // Verificar se o usuário logado é o dono do perfil ou é admin
        if ($authUser->id !== $user->id && $authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado a editar este usuário',
            ], 403);
        }

        $validated = $request->validated();

        // Se a senha foi alterada, criptografar
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso',
            'user' => $user->only(['id', 'name', 'email', 'organization']),
        ], 200);
    }

    /**
     * Obter dados do usuário logado
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Não autenticado',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $user->only(['id', 'name', 'email', 'organization']),
        ], 200);
    }
}
