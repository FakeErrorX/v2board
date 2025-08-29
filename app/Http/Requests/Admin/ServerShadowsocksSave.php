<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerShadowsocksSave extends FormRequest
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
            'parent_id' => 'nullable|integer',
            'route_id' => 'nullable|array',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'cipher' => 'required|in:aes-128-gcm,aes-192-gcm,aes-256-gcm,chacha20-ietf-poly1305,2022-blake3-aes-128-gcm,2022-blake3-aes-256-gcm',
            'obfs' => 'nullable|in:http',
            'obfs_settings' => 'nullable|array',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Server name cannot be empty',
            'group_id.required' => 'Permission group cannot be empty',
            'group_id.array' => 'Permission group format is incorrect',
            'route_id.array' => 'Route group format is incorrect',
            'parent_id.integer' => 'Parent server format is incorrect',
            'host.required' => 'Server address cannot be empty',
            'port.required' => 'Connection port cannot be empty',
            'server_port.required' => 'Backend service port cannot be empty',
            'cipher.required' => 'Encryption method cannot be empty',
            'tags.array' => 'Tags format is incorrect',
            'rate.required' => 'Rate cannot be empty',
            'rate.numeric' => 'Rate format is incorrect',
            'obfs.in' => 'Obfuscation format is incorrect',
            'obfs_settings.array' => 'Obfuscation settings format is incorrect'
        ];
    }
}
