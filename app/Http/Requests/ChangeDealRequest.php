<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ChangeDealRequest extends FormRequest
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
            'ac_contact_id' => 'required|string|max:255',
            'company_name' => 'string|max:255',
            'deal_id' => 'required|string|max:255',
            'deal_name' => 'required|string|max:255',
            'enterprise_id' => 'string|max:255',
            'last_name_adonara' => 'string|max:255',
            'phone' => 'string|max:255',
            'stage_id' => 'required|string|max:255',
            // 'status' => 'string|max:255',
            'sub_industry' => 'string|max:255',
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
