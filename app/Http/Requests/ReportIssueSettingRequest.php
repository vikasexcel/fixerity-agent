<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportIssueSettingRequest extends FormRequest
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
            'chat_deletion_days_after_issue_resolution' => "required_if:report_chat_history_delete,==,1",
            'general_report_issue_icon' => 'nullable|mimes:png,jpg,jpeg,webp|dimensions:max_width=210,max_height=210|image',
            'min_report_issue_image_upload' => "required|min:1",
            'max_report_issue_image_upload' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($value < $this->input('min_report_issue_image_upload')) {
                        $fail('The Max Report Issue Image Upload Limit must be greater than or equal to Min Report Issue Image Upload Limit.');
                    }
                }
            ],
        ];
        return $rules;
    }
}
