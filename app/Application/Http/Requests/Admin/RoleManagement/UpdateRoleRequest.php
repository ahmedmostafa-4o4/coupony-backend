<?php

namespace App\Application\Http\Requests\Admin\RoleManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = collect(explode('/', $this->path()))->last();
        // Since $this->route('role') is just the ID (because of how spatie permissions resolves, sometimes it's bound, sometimes just string).
        if ($this->route('role') instanceof \Spatie\Permission\Models\Role) {
            $roleId = $this->route('role')->id;
        } else {
            $roleId = $this->route('role');
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
