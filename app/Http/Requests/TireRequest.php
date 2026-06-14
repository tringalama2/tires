<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:12'],
            'size' => ['nullable', 'string', 'max:255'],
            'purchased_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'starting_tread' => ['required', 'numeric', 'between:0,20'],
        ];
    }
}
