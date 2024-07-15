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
            // 'type' => 'required|string|max:50',
            // 'date_time' => 'required|date',
            // 'initiated_from' => 'required|string|max:50',
            // 'initiated_by' => 'required|string|max:50',
            'list' => 'string|max:50',
            'form' => 'required',
            'form.id' => 'integer',
            'contact.id' => 'required|integer',
            'contact.email' => 'required|string|max:50',
            'contact.first_name' => 'string|max:50',
            'contact.last_name' => 'required|string|max:50',
            'contact.phone' => 'string|max:50',
            'contact.ip' => 'string|max:50',
            'contact.fields' => 'required',
            'contact.fields.1' => 'required|string|max:50',
            'contact.fields.2' => 'required|string|max:50',
            'active_subscriptions' => 'array',

        ];
    }


    public function messages(): array
    {
        return [
            'form.id.integer' => 'Form ID must be an integer',
            'contact.id.required' => 'Contact ID is required',
            'contact.id.integer' => 'Contact ID must be an integer',
            'contact.email.required' => 'Email is required',
            'contact.email.string' => 'Email must be a string',
            'contact.email.max' => 'Email must not exceed 50 characters',
            'contact.first_name.string' => 'First Name must be a string',
            'contact.first_name.max' => 'First Name must not exceed 50 characters',
            'contact.last_name.required' => 'Last Name is required',
            'contact.last_name.string' => 'Last Name must be a string',
            'contact.last_name.max' => 'Last Name must not exceed 50 characters',
            'contact.phone.string' => 'Phone must be a string',
            'contact.phone.max' => 'Phone must not exceed 50 characters',
            'contact.ip.string' => 'IP must be a string',
            'contact.ip.max' => 'IP must not exceed 50 characters',
            'contact.fields.1.required' => 'Company name is required',
            'contact.fields.1.string' => 'Company name must be a string',
            'contact.fields.1.max' => 'Company name must not exceed 50 characters',
            'contact.fields.2.required' => 'Subindustry is required',
            'contact.fields.2.string' => 'Subindustry must be a string',
            'contact.fields.2.max' => 'Subindustry must not exceed 50 characters',
            'active_subscriptions.array' => 'Active Subscriptions must be an array',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'error' => 'Validation Error',
            'status' => 422,
            'message' => $validator->errors(),
        ], 422);
        throw new HttpResponseException($response);
    }
}
