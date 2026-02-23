<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'uuid', 'exists:onboarding_stages,id'],
            'session_title' => ['required', 'string', 'max:255'],
            'session_description' => ['nullable', 'string', 'max:2000'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'scheduled_end_time' => ['required', 'date_format:H:i', 'after:scheduled_start_time'],
            'location_type' => ['required', 'in:online,onsite,hybrid'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'physical_location' => ['nullable', 'string', 'max:500'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['uuid', 'exists:client_contacts,id'],
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
