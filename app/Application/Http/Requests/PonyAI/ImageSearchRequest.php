<?php

namespace App\Application\Http\Requests\PonyAI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ImageSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:6144', // KB → 6 MB
            ],
            'message' => ['nullable', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function uploadedImage(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('image');

        return $file;
    }

    public function extraMessage(): string
    {
        return trim((string) $this->input('message', ''));
    }

    public function conversationId(): ?string
    {
        $value = $this->input('conversation_id');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
