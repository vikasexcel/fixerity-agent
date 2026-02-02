<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtherServiceSubCategoryRequest extends FormRequest
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
            'name' => 'required|unique:other_service_sub_category,name,' . $this->get('id'),
            'category_icon' => 'required|mimes:jpeg,jpg,png|image|dimensions:max_width=250,max_height=250',
            'status' => 'required|boolean'
        ];
        if(!empty($this->get('id')))
        {
            unset($rules['category_icon']);
        }
        return $rules;
    }
}
