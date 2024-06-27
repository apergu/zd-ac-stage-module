<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ContactCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|max:50',
            'date_time' => 'required|date',
            'initiated_from' => 'required|string|max:50',
            'initiated_by' => 'required|string|max:50',
            'list' => 'required|string|max:50',
            'form' => 'required',
            'form.id' => 'required|integer',
            'contact.id' => 'required|integer',
            'contact.email' => 'required|string|max:50',
            'contact.first_name' => 'required|string|max:50',
            'contact.last_name' => 'required|string|max:50',
            'contact.phone' => 'required|string|max:50',
            'contact.ip' => 'required|string|max:50',
            'contact.field' => 'required',
            'active_subscription' => 'required|array',

        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
