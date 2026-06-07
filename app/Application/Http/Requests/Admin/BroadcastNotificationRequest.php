<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'string|in:in_app,push,email,sms',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|in:all,customer,store_owner,admin',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'uuid|exists:users,id',
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roles = $this->input('target_roles', []);
            $userIds = $this->input('target_user_ids', []);
            
            if (empty($roles) && empty($userIds)) {
                $validator->errors()->add('target', 'You must specify either target_roles or target_user_ids.');
            }
        });
    }
}
