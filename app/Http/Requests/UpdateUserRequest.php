<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => 'string|max:255', // Puedes ajustar las reglas según tus necesidades
            'email' => 'email|unique:users,email,' . $this->route('user'), // Asegura que el email sea único, excluyendo el actual
            'password' => 'nullable|string|min:8', // Contraseña es opcional, pero si se proporciona, debe cumplir con las reglas
            'role' => 'nullable|in:candidate,company',
        ];
    }
}
