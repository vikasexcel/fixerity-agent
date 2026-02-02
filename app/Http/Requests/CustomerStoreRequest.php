<?php

namespace App\Http\Requests;

use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class CustomerStoreRequest extends FormRequest
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
        //dd($this->get('country_code')."---".$this->get('contact_number'));
        //(($this->getHost() == "fox-jek.startuptrinity.com")? 'email|' : '' )
        $rules = [
            'first_name' => 'required',
//            'last_name' => 'required',
//            "email" => "required|email:rfc,dns|unique:users,email,".$this->get('id'),
            'email' => [
                'required',new EmailRule(),
                Rule::unique('users','email')->where(function($query) {
                    $query->where('email', '=', $this->get('email'));
                    $query->where('id', '!=', $this->get('id'));
                    $query->where('deleted_at', '=', null);
                })
            ],
//            'email' => 'required|'.(($this->getHost() == "fox-jek.startuptrinity.com")? 'email|' : '' ).'unique:users,email,'.$this->get('id'),
            'password' => 'required|min:6|max:16',
            're_type_password' => 'required|same:password',
            //'contact_number' => 'required|numeric|unique:users,contact_number,country_code',
            'contact_number' => [
                'required ','numeric',
                Rule::unique('users')->where(function($query) {
                    $query->where('contact_number', '=', $this->get('contact_number'));
                    $query->where('country_code', '=', $this->get('country_code'));
                    $query->where('id', '!=', $this->get('id'));
                    $query->where('deleted_at', '=', null);
                }),
                'regex:/^[^.]*$/'
           ],
            //'full_number' => "required|numeric|unique:users,contact_number,".$this->get('id'),
            //'contact_numbers' => "required|numeric|unique:users,contact_number,".$this->get('id'),
//            'gender' => 'required',
            'avatar' => 'nullable',
        ];
        if (!empty($this->get('id'))) {
            unset($rules['password']);
            unset($rules['re_type_password']);
            //unset($rules['contact_number']);
            //unset($rules['contact_numbers']);
            //unset($rules['full_number']);
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
