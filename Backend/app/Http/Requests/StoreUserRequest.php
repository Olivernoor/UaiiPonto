<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'organization' => 'required|string|unique:users,organization|max:255',
            'password' => [
                'required',
                'confirmed',
                'min:6',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve ser um texto.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'O campo e-mail deve conter um e-mail válido.',
            'email.unique' => 'Este e-mail já foi cadastrado no sistema.',
            
            'organization.required' => 'O campo organização é obrigatório.',
            'organization.string' => 'O campo organização deve ser um texto.',
            'organization.unique' => 'Esta organização já foi cadastrada no sistema.',
            'organization.max' => 'O campo organização não pode ter mais de 255 caracteres.',
            
            'password.required' => 'O campo senha é obrigatório.',
            'password.confirmed' => 'As senhas não conferem.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
        ];
    }
}
