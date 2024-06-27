<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RotationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'rotated_on' => ['required', 'date'],
            'odometer' => ['required', 'integer'],
        ];
    }

    public function authorize(): true
    {
        return true;
    }
}
