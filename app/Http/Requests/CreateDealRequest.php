<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CreateDealRequest extends FormRequest
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
            "ac_contact_id" => "required|string|max:255",
            "company_name" => "string|max:255",
            "deal_id" => "required|string|max:255",
            "deal_name" => "required|string|max:255",
            "enterprise_id" => "string|max:255",
            "last_name_adonara" => "string|max:255",
            "lead_id" => "required|string|max:255",
            "phone" => "string|max:255",
            "stage_id" => "string|max:255",
            "sub_industry" => "required|string|max:255"

        ];
    }


    public function messages(): array
    {
        return [
            "ac_contact_id.required" => "The ac_contact_id field is required",
            "company_name.string" => "The company_name field must be a string",
            "deal_id.required" => "The deal_id field is required",
            "deal_name.required" => "The deal_name field is required",
            "enterprise_id.string" => "The enterprise_id field must be a string",
            "last_name_adonara.string" => "The last_name_adonara field must be a string",
            "lead_id.required" => "The lead_id field is required",
            "phone.string" => "The phone field must be a string",
            "stage_id.string" => "The stage_id field must be a string",
            "sub_industry.required" => "The sub_industry field is required"
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
            "error" => "Validation Error",
            "status" => 422,
            "message" => $validator->errors(),
        ], 422);
        throw new HttpResponseException($response);
    }
}
