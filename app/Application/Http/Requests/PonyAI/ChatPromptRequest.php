<?php

namespace App\Application\Http\Requests\PonyAI;

use Illuminate\Foundation\Http\FormRequest;

class ChatPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'string', 'uuid'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function message(): string
    {
        return trim((string) $this->input('message'));
    }

    public function conversationId(): ?string
    {
        $value = $this->input('conversation_id');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
