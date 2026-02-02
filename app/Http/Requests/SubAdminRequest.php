<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubAdminRequest extends FormRequest
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
        if (!empty($this->get('id'))) {
            return [
                //
                'name' => "required",
                'email' => 'required|unique:super_admin,email,' . $this->get('id'),
            ];
        }else{
            return [
                //
                'name' => "required",
                'email' => 'required|unique:super_admin,email,' . $this->get('id'),
                'password' => "required",
            ];
        }
    }
}
