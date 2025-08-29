<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CouponGenerate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'generate_count' => 'nullable|integer|max:500',
            'name' => 'required',
            'type' => 'required|in:1,2',
            'value' => 'required|integer',
            'started_at' => 'required|integer',
            'ended_at' => 'required|integer',
            'limit_use' => 'nullable|integer',
            'limit_use_with_user' => 'nullable|integer',
            'limit_plan_ids' => 'nullable|array',
            'limit_period' => 'nullable|array',
            'code' => ''
        ];
    }

    public function messages()
    {
        return [
            'generate_count.integer' => 'Generation count must be numeric',
            'generate_count.max' => 'Maximum generation count is 500',
            'name.required' => 'Name cannot be empty',
            'type.required' => 'Type cannot be empty',
            'type.in' => 'Type format is incorrect',
            'value.required' => 'Amount or percentage cannot be empty',
            'value.integer' => 'Amount or percentage format is incorrect',
            'started_at.required' => 'Start time cannot be empty',
            'started_at.integer' => 'Start time format is incorrect',
            'ended_at.required' => 'End time cannot be empty',
            'ended_at.integer' => 'End time format is incorrect',
            'limit_use.integer' => 'Maximum usage count format is incorrect',
            'limit_use_with_user.integer' => 'User usage limit count format is incorrect',
            'limit_plan_ids.array' => 'Specified subscription format is incorrect',
            'limit_period.array' => 'Specified period format is incorrect'
        ];
    }
}
