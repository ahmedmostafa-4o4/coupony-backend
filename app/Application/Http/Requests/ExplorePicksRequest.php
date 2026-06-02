<?php

namespace App\Application\Http\Requests;

class ExplorePicksRequest extends ExploreBootstrapRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'sort_by' => ['nullable', 'string', 'in:trending,newest,most_saved,highest_discount'],
        ];
    }
}
