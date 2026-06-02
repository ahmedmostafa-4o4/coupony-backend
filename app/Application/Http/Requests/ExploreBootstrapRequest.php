<?php

namespace App\Application\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExploreBootstrapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'interest_id' => ['nullable', 'integer'],
            'activity_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:200'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateInterestId($validator);
            $this->validateActivityId($validator);
        });
    }

    protected function validateInterestId(Validator $validator): void
    {
        $interestId = $this->input('interest_id');

        if ($interestId === null) {
            return;
        }

        $exists = DB::table('categories')
            ->where('id', $interestId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new HttpResponseException(
                new JsonResponse([
                    'success' => false,
                    'message' => 'The selected interest_id is invalid. It must correspond to an active category.',
                ], 400)
            );
        }
    }

    protected function validateActivityId(Validator $validator): void
    {
        $activityId = $this->input('activity_id');

        if ($activityId === null) {
            return;
        }

        $exists = DB::table('store_categories')
            ->where('id', $activityId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new HttpResponseException(
                new JsonResponse([
                    'success' => false,
                    'message' => 'The selected activity_id is invalid. It must correspond to an active store category.',
                ], 400)
            );
        }
    }
}
