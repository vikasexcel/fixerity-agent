<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
//            "email" => "required|email:rfc,dns|unique:providers,email",
//            "full_number" => "required|numeric|unique:providers,contact_number",
//            "contact_number" => "required|numeric|unique:providers,contact_number",
            //"contact_numbers" => "required|numeric|unique:providers,contact_number",
            'email' => [
                'required',
                Rule::unique('providers','email')->where(function($query) {
                    $query->where('email', '=', $this->get('email'));
                    $query->where('deleted_at', '=', null);
                })
            ],
            'contact_number' => [
                'required ','numeric',
                Rule::unique('providers')->where(function($query) {
                    $query->where('contact_number', '=', $this->get('contact_number'));
                    $query->where('country_code', '=', $this->get('country_code'));
                    $query->where('deleted_at', '=', null);
                })
            ],
            "name" => "required",
            "gender" => "required|in:1,2",
            "password" => "required|min:6|max:18",
            "confirm_password" => "required|same:password"
        ];
    }
    public function messages()
    {
        return [
            'contact_number.required' => 'Contact number required',
            'contact_number.numeric' => 'Contact number must be numeric',
            'contact_number.unique' => 'Contact number already exist',
//            'contact_numbers.required' => 'please enter valid contact number!',
        ];
    }
}
