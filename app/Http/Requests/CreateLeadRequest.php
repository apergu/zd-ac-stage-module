<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CreateLeadRequest extends FormRequest
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
            'ac_contact_id' => 'integer',
            'company_name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'enterprise_id' => 'string|max:255',
            'first_name' => 'string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'string|max:255',
            'status' => 'string|max:255',
            'sub_industry' => 'required|string|max:255',
            'zd_lead_id' => 'required|integer',
        ];
    }



    public function messages(): array
    {
        return [
            'ac_contact_id.integer' => 'The ac_contact_id field must be an integer',
            'company_name.required' => 'The company_name field is required',
            'company_name.string' => 'The company_name field must be a string',
            'company_name.max' => 'The company_name field must not exceed 255 characters',
            'email.required' => 'The email field is required',
            'email.string' => 'The email field must be a string',
            'email.max' => 'The email field must not exceed 255 characters',
            'enterprise_id.string' => 'The enterprise_id field must be a string',
            'enterprise_id.max' => 'The enterprise_id field must not exceed 255 characters',
            'first_name.string' => 'The first_name field must be a string',
            'first_name.max' => 'The first_name field must not exceed 255 characters',
            'last_name.required' => 'The last_name field is required',
            'last_name.string' => 'The last_name field must be a string',
            'last_name.max' => 'The last_name field must not exceed 255 characters',
            'phone.string' => 'The phone field must be a string',
            'phone.max' => 'The phone field must not exceed 255 characters',
            'status.string' => 'The status field must be a string',
            'status.max' => 'The status field must not exceed 255 characters',
            'sub_industry.required' => 'The sub_industry field is required',
            'sub_industry.string' => 'The sub_industry field must be a string',
            'sub_industry.max' => 'The sub_industry field must not exceed 255 characters',
            'zd_lead_id.required' => 'The zd_lead_id field is required',
            'zd_lead_id.integer' => 'The zd_lead_id field must be an integer',
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
