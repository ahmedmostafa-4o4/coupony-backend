<?php

namespace App\Application\Http\Requests\Admin\UserManagement;

use App\Domain\User\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
