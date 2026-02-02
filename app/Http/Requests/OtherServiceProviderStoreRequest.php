<?php

namespace App\Http\Requests;

use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OtherServiceProviderStoreRequest extends FormRequest
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
        $rules = [
            'first_name' => 'required',
//            'last_name' => 'required',
//            'email' => 'required|email:rfc,dns|unique:providers,email,' . $this->get('id'),
            'avatar' => 'nullable|image|max:250',
//            'contact_number' => 'required|unique:providers,contact_number,' . $this->get('id'),
//            'full_number' => 'required|unique:providers,contact_number,' . $this->get('id'),
            'contact_numbers' => 'required|unique:providers,contact_number,' . $this->get('id'),
            'email' => [
                'required', new EmailRule(),
                Rule::unique('providers','email')->where(function($query) {
                    $query->where('email', '=', $this->get('email'));
                    $query->where('id', '!=', $this->get('id'));
                    $query->where('deleted_at', '=', null);
                })
            ],
            'contact_number' => [
                'required ','numeric',
                Rule::unique('providers')->where(function($query) {
                    $query->where('contact_number', '=', $this->get('contact_number'));
                    $query->where('country_code', '=', $this->get('country_code'));
                    $query->where('id', '!=', $this->get('id'));
                    $query->where('deleted_at', '=', null);
                }),
                'regex:/^[^.]*$/'
            ],
            'pass' => 'required|min:6|max:16',
            'confirm_password' => 'required|same:pass',
            'service_radius' => 'nullable',
            'gender' => 'required',
//            'vehicle_type_id' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'long' => 'required',
//
            'bank_name' => 'nullable',
            'account_number' => 'nullable',
            'payment_email' => ['nullable',new EmailRule()],
            'bank_location' => 'nullable',
            'holder_name' => 'nullable',
            'bic_swift_code' => 'nullable',

        ];
        if (!empty($this->get('id'))) {
            unset($rules['pass']);
            unset($rules['confirm_password']);
        }
        return $rules;
    }
    public function messages()
    {
        return [
            'contact_numbers.required' => 'please enter valid contact number!',
            'first_name.required' => 'please enter full name!',
        ];
    }
}
