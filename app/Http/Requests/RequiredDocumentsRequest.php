<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequiredDocumentsRequest extends FormRequest
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
        $rules= [
            'service_cat_id' => 'required',
            'name' => 'required|unique:required_documents,name,NULL,service_cat_id,service_cat_id,'.$this->get('service_cat_id').''.$this->get('id'),
            'status' => 'required',
        ];
        $unrules = [
            'service_cat_id' => 'required',
            'name' => [
                'required',
                \Illuminate\Validation\Rule::unique('required_documents', 'name')
                    ->where('service_cat_id', $this->get('service_cat_id'))
                    ->where('name', $this->get('name'))
                    ->whereNotIn('id', [$this->get('id')]),
            ],
            'status' => 'required',
        ];
        if(!empty($this->get('id')))
        {
//            unset($rules['document_name']);
            isset($unrules['name']);
            return $unrules;
        }else{
            return $rules;
        }
    }
}
