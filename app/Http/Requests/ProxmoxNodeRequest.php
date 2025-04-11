<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProxmoxNodeRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'group' => 'sometimes|nullable|string|max:255',
            'ip_address' => 'sometimes|nullable|ip',
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
            'name.string' => 'The node name must be a string',
            'group.string' => 'The node group must be a string',
            'ip_address.ip' => 'Please provide a valid IP address',
        ];
    }
}
