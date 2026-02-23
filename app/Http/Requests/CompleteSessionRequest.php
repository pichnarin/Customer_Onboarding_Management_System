<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompleteSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completion_notes'   => ['required', 'string', 'max:2000'],
            'end_proof_media_id' => ['required', 'uuid', 'exists:media,id'],
            'student_count'      => ['required', 'integer', 'min:0'],
            'end_latitude'       => ['required', 'numeric', 'between:-90,90'],
            'end_longitude'      => ['required', 'numeric', 'between:-180,180'],
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
