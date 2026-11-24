<?php

namespace Modules\Rooms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PairDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:9'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }
}
