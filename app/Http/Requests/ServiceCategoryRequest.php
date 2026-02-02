<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceCategoryRequest extends FormRequest
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
            'name' => 'required|unique:service_category,name,' . $this->get('id'),
            /*'fl_name' => 'required|unique:service_category,fl_name,' . $this->get('id'),
            'cb_name' => 'required|unique:service_category,cb_name,' . $this->get('id'),
            'cs_name' => 'required|unique:service_category,cs_name,' . $this->get('id'),
            'ct_name' => 'required|unique:service_category,ct_name,' . $this->get('id'),
            'jp_name' => 'required|unique:service_category,jp_name,' . $this->get('id'),
            'ko_name' => 'required|unique:service_category,ko_name,' . $this->get('id'),
            'fr_name' => 'required|unique:service_category,fr_name,' . $this->get('id'),
            'sp_name' => 'required|unique:service_category,sp_name,' . $this->get('id'),
            'gr_name' => 'required|unique:service_category,gr_name,' . $this->get('id'),*/
//            'icon' => 'required|dimensions:min_width=50,min_height=50,max_width=100,max_height=100|max:100',
            'icon' => 'required|mimes:png|dimensions:max_width=200,max_height=200|image',
            'icon_type' => 'nullable|in:3,4',
            'status' => 'required'
        ];
        if (!empty($this->get('id'))) {
            $rules = [
//                'icon' => 'nullable|dimensions:min_width=50,min_height=50,max_width=100,max_height=100|max:100',
                'name' => 'required|unique:service_category,name,' . $this->get('id'),
                'icon' => 'nullable|dimensions:max_width=200,max_height=200|image',
            ];
        }
        return $rules;
    }

    public function messages()
    {
        return [
//            'icon.max' => 'Icon max size 100kb',
            'icon.dimensions' => 'Icon Max Dimension 200*200',
        ];
    }
}
