<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OnBoardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'system_id' => ['required', 'uuid', 'exists:systems,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'expected_start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date', 'after:expected_start_date'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
