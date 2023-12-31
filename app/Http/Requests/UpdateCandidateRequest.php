<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'exists:users,id',
            'full_name' => 'string|max:255',
            'gender' => 'string|max:20',
            'date_of_birth' => 'date',
            'address' => 'string|max:255',
            'phone_number' => 'string|max:20',
            'work_experience' => 'string',
            'education' => 'string',
            'skills' => 'string',
            'certifications' => 'string',
            'languages' => 'string',
            'references' => 'string',
            'expected_salary' => 'numeric|min:0',
            'cv_path' => 'nullable|string|max:255',
            'photo_path' => 'nullable|string|max:255',
            'status' => 'in:Activo,Inactivo,Pendiente',
        ];
    }
}
