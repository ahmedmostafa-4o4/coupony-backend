<?php

namespace App\Application\Http\Requests\Admin\UserManagement;

use App\Domain\User\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(UserStatus::cases(), 'value'))],
        ];
    }
}
