<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsRequest extends FormRequest
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
            'website_name' => 'required',
//            'website_logo' => 'nullable|image|dimensions:min_width=250,max_width=400,min_height=100,max_height=250|max:500|mimes:png',
            'website_logo' => 'nullable|image|mimes:png',
            "website_favicon" => "nullable|mimes:jpg,png,ico|max:50|dimensions:max_width=50,max_height=50",
//            "contact_no" => "nullable|numeric",
            "contact_no" => "nullable|regex:/(^[0-9+-]+$)+/",
            "email" => "nullable|email",
            'used_user_discount' => "numeric",
            'used_user_discount_type' => "required_with:used_user_discount",
            'refer_user_discount' => "numeric",
            'refer_user_discount_type' => "required_with:refer_user_discount",
        ];
        if ($this->get('used_user_discount') == 0) {
            unset($rules['used_user_discount_type']);
        }
        if ($this->get('refer_user_discount') == 0) {
            unset($rules['refer_user_discount_type']);
        }
        return $rules;
    }
}
