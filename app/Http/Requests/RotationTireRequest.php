<?php

namespace App\Http\Requests;

use App\Enums\TirePosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RotationTireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rotation_id' => ['required', 'integer'],
            'tire_id' => ['required', 'integer'],
            'position' => ['required', new Enum(TirePosition::class)],
            'tread' => ['required', 'integer', 'max_digits:2'],
        ];
    }

    public function authorize(): true
    {
        return true;
    }
}
