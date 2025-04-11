<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProxmoxClusterRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'api_host' => 'required|string|max:255',
            'api_port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'token_name' => 'required|string|max:255',
            'token_value' => 'required|string|max:255',
            'verify_ssl' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'A cluster name is required',
            'api_host.required' => 'The Proxmox API host is required',
            'api_port.required' => 'The Proxmox API port is required',
            'api_port.integer' => 'The Proxmox API port must be a valid port number',
            'username.required' => 'The Proxmox username is required',
            'token_name.required' => 'The Proxmox API token name is required',
            'token_value.required' => 'The Proxmox API token value is required',
        ];
    }
}
