<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerVmessSave extends FormRequest
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
            'tls' => 'required',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric',
            'network' => 'required|in:tcp,kcp,ws,http,domainsocket,quic,grpc,httpupgrade,xhttp',
            'networkSettings' => 'nullable|array',
            'networkSettings.security' => 'nullable|in:auto,aes-128-gcm,chacha20-poly1305,none',
            'ruleSettings' => 'nullable|array',
            'tlsSettings' => 'nullable|array',
            'dnsSettings' => 'nullable|array'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Node name cannot be empty',
            'group_id.required' => 'Permission group cannot be empty',
            'group_id.array' => 'Permission group format is incorrect',
            'route_id.array' => 'Route group format is incorrect',
            'parent_id.integer' => 'Parent ID format is incorrect',
            'host.required' => 'Node address cannot be empty',
            'port.required' => 'Connection port cannot be empty',
            'server_port.required' => 'Backend service port cannot be empty',
            'tls.required' => 'TLS cannot be empty',
            'tags.array' => 'Tags format is incorrect',
            'rate.required' => 'Rate cannot be empty',
            'rate.numeric' => 'Rate format is incorrect',
            'network.required' => 'Transport protocol cannot be empty',
            'network.in' => 'Transport protocol format is incorrect',
            'networkSettings.array' => 'Transport protocol configuration is incorrect',
            'networkSettings.security.in' => 'vmess encryption type can only be: auto, aes-128-gcm, chacha20-poly1305, none',
            'ruleSettings.array' => 'Rule configuration is incorrect',
            'tlsSettings.array' => 'TLS configuration is incorrect',
            'dnsSettings.array' => 'DNS configuration is incorrect'
        ];
    }
}
