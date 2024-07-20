<?php

namespace App\Http\Requests;

use App\Enums\TireStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'tin' => ['nullable', 'max:17'],
            'label' => ['required', 'max:255'],
            'brand' => ['nullable', 'max:255'],
            'model' => ['nullable', 'max:255'],
            'desc' => ['nullable', 'max:255'],
            'size' => ['nullable', 'max:255'],
            'purchased_on' => ['required', 'date'],
            'notes' => ['nullable', 'max:255'],
            'status' => ['required', new Enum(TireStatus::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
