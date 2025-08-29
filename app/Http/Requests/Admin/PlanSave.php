<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlanSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'content' => '',
            'group_id' => 'required',
            'transfer_enable' => 'required',
            'device_limit' => 'nullable|integer',
            'month_price' => 'nullable|integer',
            'quarter_price' => 'nullable|integer',
            'half_year_price' => 'nullable|integer',
            'year_price' => 'nullable|integer',
            'two_year_price' => 'nullable|integer',
            'three_year_price' => 'nullable|integer',
            'onetime_price' => 'nullable|integer',
            'reset_price' => 'nullable|integer',
            'reset_traffic_method' => 'nullable|integer|in:0,1,2,3,4',
            'capacity_limit' => 'nullable|integer',
            'speed_limit' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Plan name cannot be empty',
            'type.required' => 'Plan type cannot be empty',
            'type.in' => 'Plan type format is incorrect',
            'group_id.required' => 'Permission group cannot be empty',
            'transfer_enable.required' => 'Traffic cannot be empty',
            'device_limit.integer' => 'Device limit format is incorrect',
            'month_price.integer' => 'Monthly payment amount format is incorrect',
            'quarter_price.integer' => 'Quarterly payment amount format is incorrect',
            'half_year_price.integer' => 'Half-year payment amount format is incorrect',
            'year_price.integer' => 'Annual payment amount format is incorrect',
            'two_year_price.integer' => 'Two-year payment amount format is incorrect',
            'three_year_price.integer' => 'Three-year payment amount format is incorrect',
            'onetime_price.integer' => 'One-time amount is incorrect',
            'reset_price.integer' => 'Traffic reset package amount is incorrect',
            'reset_traffic_method.integer' => 'Traffic reset method format is incorrect',
            'reset_traffic_method.in' => 'Traffic reset method format is incorrect',
            'capacity_limit.integer' => 'User capacity limit format is incorrect',
            'speed_limit.integer' => 'Speed limit format is incorrect'
        ];
    }
}
