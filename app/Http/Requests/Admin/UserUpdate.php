<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email:strict',
            'password' => 'nullable|min:8',
            'transfer_enable' => 'numeric',
            'device_limit' => 'nullable|integer',
            'expired_at' => 'nullable|integer',
            'banned' => 'required|in:0,1',
            'plan_id' => 'nullable|integer',
            'commission_rate' => 'nullable|integer|min:0|max:100',
            'discount' => 'nullable|integer|min:0|max:100',
            'is_admin' => 'required|in:0,1',
            'is_staff' => 'required|in:0,1',
            'u' => 'integer',
            'd' => 'integer',
            'balance' => 'integer',
            'commission_type' => 'integer',
            'commission_balance' => 'integer',
            'remarks' => 'nullable',
            'speed_limit' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email cannot be empty',
            'email.email' => 'Email format is incorrect',
            'transfer_enable.numeric' => 'Traffic format is incorrect',
            'device_limit.integer' => 'Device limit format is incorrect',
            'expired_at.integer' => 'Expiration time format is incorrect',
            'banned.required' => 'Ban status cannot be empty',
            'banned.in' => 'Ban status format is incorrect',
            'is_admin.required' => 'Admin status cannot be empty',
            'is_admin.in' => 'Admin status format is incorrect',
            'is_staff.required' => 'Staff status cannot be empty',
            'is_staff.in' => 'Staff status format is incorrect',
            'plan_id.integer' => 'Subscription plan format is incorrect',
            'commission_rate.integer' => 'Referral commission rate format is incorrect',
            'commission_rate.nullable' => 'Referral commission rate format is incorrect',
            'commission_rate.min' => 'Referral commission rate minimum is 0',
            'commission_rate.max' => 'Referral commission rate maximum is 100',
            'discount.integer' => 'Exclusive discount rate format is incorrect',
            'discount.nullable' => 'Exclusive discount rate format is incorrect',
            'discount.min' => 'Exclusive discount rate minimum is 0',
            'discount.max' => 'Exclusive discount rate maximum is 100',
            'u.integer' => 'Upload traffic format is incorrect',
            'd.integer' => 'Download traffic format is incorrect',
            'balance.integer' => 'Balance format is incorrect',
            'commission_balance.integer' => 'Commission format is incorrect',
            'password.min' => 'Password minimum length is 8 characters',
            'speed_limit.integer' => 'Speed limit format is incorrect'
        ];
    }
}
