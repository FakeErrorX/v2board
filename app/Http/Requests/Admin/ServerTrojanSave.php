<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerTrojanSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'show' => '',
            'name' => 'required',
            'group_id' => 'required|array',
            'route_id' => 'nullable|array',
            'parent_id' => 'nullable|integer',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'network' => 'required',
            'network_settings' => 'nullable',
            'allow_insecure' => 'nullable|in:0,1',
            'server_name' => 'nullable',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Node name cannot be empty',
            'group_id.required' => 'Permission group cannot be empty',
            'group_id.array' => 'Permission group format is incorrect',
            'route_id.array' => 'Route group format is incorrect',
            'parent_id.integer' => 'Parent node format is incorrect',
            'host.required' => 'Node address cannot be empty',
            'port.required' => 'Connection port cannot be empty',
            'server_port.required' => 'Backend service port cannot be empty',
            'allow_insecure.in' => 'Allow insecure format is incorrect',
            'tags.array' => 'Tags format is incorrect',
            'rate.required' => 'Rate cannot be empty',
            'rate.numeric' => 'Rate format is incorrect'
        ];
    }
}
