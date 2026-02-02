<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtherServicePackagesRequest extends FormRequest
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
            'category' => 'required',
            'package_name' => 'required',
            'package_price' => 'required',
            'max_book_quantity' => 'required',
            'description' => 'required',
        ];
    }
}
