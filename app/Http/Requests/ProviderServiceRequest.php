<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProviderServiceRequest extends FormRequest
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
        $provider_id = Auth::guard('on_demand')->user()->id;
        return [
            "user_name" => "required",
            "landmark" => "required",
            "address" => "required",
//            "email" => "required",
            "service_radius" => "required",
//            "contact_number" => "required",
            "service_category" => "required",
            "service_sub_category" => "required",
            "package_name" => "required",
            "package_price" => "required|numeric",
            "max_book_quantity" => "required|numeric",
            "description" => "nullable",
            "country_code" => "required",
            "contact_numbers" => [
                "required", "numeric",
                Rule::unique('providers','contact_number')->where(function($query) use($provider_id) {
                    $query->where('id', '!=', $provider_id);
                    //$query->where('login_type', '=', 'email');
                    $query->where('contact_number', '=', $this->get('contact_number'));
                    $query->where('country_code', '=', $this->get('country_code'));
                    $query->where('deleted_at', '=', null);
                    $query->where('provider_type', '=', 3);
                })
            ],
            "email" => [
                "required","email",
                Rule::unique('providers','email')->where(function($query) use($provider_id) {
                    $query->where('id', '!=', $provider_id);
                    $query->where('email', '=', $this->get('email'));
                    $query->where('deleted_at', '=', null);
                    $query->where('provider_type', '=', 3);
                })
            ],
        ];
    }
}
