<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushNotificationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'notification_type' => 'required|numeric|gt:0',
            'title' => 'required|string|max:20',
            'message' => 'required|string|max:50'
        ];
    }
}
