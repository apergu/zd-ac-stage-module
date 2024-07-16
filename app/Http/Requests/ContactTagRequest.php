<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ContactTagRequest extends FormRequest
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
            'contact.id' => 'required|integer',
            'tag_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'contact.id.required' => 'Contact ID is required',
            'contact.id.integer' => 'Contact ID must be an integer',
            'tag_id.required' => 'Tag ID is required',
            'tag_id.integer' => 'Tag ID must be an integer',
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
