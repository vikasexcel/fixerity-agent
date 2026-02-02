<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PromocodeDetails;

class PromocodeDetailsRequest extends FormRequest
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
        $id = $this->input('id'); // Get the ID from the request input
        $currentPromoCode = PromocodeDetails::find($id)->promo_code ?? ''; // Get the current promo code from the database

        return [
            'code_name' => [
                'required',
                'min:5', // Minimum length
                'regex:/^(?=.*[a-zA-Z])[a-zA-Z0-9!@#$%&*_ ]+$/', // Regex for allowed characters
                function ($attribute, $value, $fail) use ($currentPromoCode) {
                    // Check if the promo code has changed
                    if ($value !== $currentPromoCode) {
                        // Validate uniqueness only if the promo code is changed
                        $exists = PromocodeDetails::where('promo_code', $value)
                            ->where('id', '!=', $this->input('id')) // Ensure it's not the current record
                            ->where('service_cat_id', $this->input('service_cat_id')) // Match same service category
                            ->exists();

                        if ($exists) {
                            $fail('The promo code has already been taken.');
                        }
                    }
                },
            ],
            'expiry_date_time' => 'required',
            'discount_type' => 'required',
            'discount_amount' => 'required|numeric|min:0',
            'usage_limit' => 'required|numeric|min:1',
            'description' => 'required',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'coupon_limit' => 'nullable|numeric|min:1',
        ];
    }
}
