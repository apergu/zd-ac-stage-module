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
            'zd_lead_id' => 'required|integer',
            // 'ac_contact_id' => 'required|integer',
            'first_name' => 'string|max:255',
            'last_name' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:255',
            'sub_industry' => 'required|string|max:255',
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
