<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAffiliateClickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_name' => ['required', 'string', 'max:100'],
            'service_id' => ['nullable', 'string', 'max:50'],
            'tmdb_id' => ['required', 'integer', 'min:1'],
            'media_type' => ['required', 'string', 'in:movie,tv'],
            'link' => ['required', 'url', 'max:500'],
        ];
    }
}
