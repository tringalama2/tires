<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year' => 'required|integer|between:1900,9999',
            'make' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'vin' => 'required|string|max:17',
            'nickname' => 'required|string|max:50',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
