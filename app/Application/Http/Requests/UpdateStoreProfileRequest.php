<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStoreProfileRequest extends FormRequest
{
    private const ALLOWED_TOP_LEVEL_FIELDS = [
        'name',
        'description',
        'email',
        'phone',
        'logo_url',
        'banner_url',
        'socials',
        'hours',
        '_method'
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo_url' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'banner_url' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'socials' => 'nullable|array',
            'socials.*.social_id' => 'required_with:socials|exists:socials,id',
            'socials.*.link' => 'required_with:socials|url|max:500',
            'hours' => 'nullable|array|size:7',
            'hours.*.day_of_week' => ['required_with:hours', 'integer', Rule::in(range(0, 6))],
            'hours.*.open_time' => 'nullable|date_format:H:i',
            'hours.*.close_time' => 'nullable|date_format:H:i',
            'hours.*.is_closed' => 'required_with:hours|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $unexpectedFields = collect(array_keys($this->all()))
                ->diff(self::ALLOWED_TOP_LEVEL_FIELDS);

            foreach ($unexpectedFields as $field) {
                $validator->errors()->add($field, 'This field is not allowed.');
            }

            foreach ($this->input('socials', []) as $index => $social) {
                if (!is_array($social)) {
                    continue;
                }

                $unexpectedNestedFields = collect(array_keys($social))
                    ->diff(['social_id', 'link']);

                foreach ($unexpectedNestedFields as $field) {
                    $validator->errors()->add("socials.{$index}.{$field}", 'This field is not allowed.');
                }
            }

            $hours = $this->input('hours', []);

            foreach ($hours as $index => $hour) {
                if (!is_array($hour)) {
                    continue;
                }

                $unexpectedNestedFields = collect(array_keys($hour))
                    ->diff(['day_of_week', 'open_time', 'close_time', 'is_closed']);

                foreach ($unexpectedNestedFields as $field) {
                    $validator->errors()->add("hours.{$index}.{$field}", 'This field is not allowed.');
                }

                $isClosed = filter_var($hour['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $openTime = $hour['open_time'] ?? null;
                $closeTime = $hour['close_time'] ?? null;

                if ($isClosed === false) {
                    if (blank($openTime)) {
                        $validator->errors()->add("hours.{$index}.open_time", 'The open time field is required when the day is open.');
                    }

                    if (blank($closeTime)) {
                        $validator->errors()->add("hours.{$index}.close_time", 'The close time field is required when the day is open.');
                    }
                }

                if (!blank($openTime) && !blank($closeTime) && $closeTime <= $openTime) {
                    $validator->errors()->add("hours.{$index}.close_time", 'The close time must be after the open time.');
                }
            }

            if (!empty($hours)) {
                $dayOfWeeks = collect($hours)->pluck('day_of_week');

                if ($dayOfWeeks->filter(fn($value) => !is_null($value))->unique()->count() !== 7) {
                    $validator->errors()->add('hours', 'The hours field must include exactly one entry for each day of the week.');
                }
            }
        });
    }
}
