<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiftcardGenerate extends FormRequest
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
            'type' => 'required|in:1,2,3,4,5',
            'value' => ['required_if:type,1,2,3,5', 'nullable', 'integer'],
            'plan_id' => ['required_if:type,5', 'nullable','integer'],
            'started_at' => 'required|integer',
            'ended_at' => 'required|integer',
            'limit_use' => 'nullable|integer',
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
            'value.required' => 'Value cannot be empty',
            'value.integer' => 'Value format is incorrect',
            'plan_id.required' => 'Subscription cannot be empty',
            'started_at.required' => 'Start time cannot be empty',
            'started_at.integer' => 'Start time format is incorrect',
            'ended_at.required' => 'End time cannot be empty',
            'ended_at.integer' => 'End time format is incorrect',
            'limit_use.integer' => 'Maximum usage count format is incorrect'
        ];
    }
}
