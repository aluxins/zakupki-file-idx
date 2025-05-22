<?php

namespace App\Http\Requests\api;

use Illuminate\Foundation\Http\FormRequest;

class FilesUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "*.id" => ['required', 'integer', 'min:1'],
            "*.url" => ['required', 'string', 'max:2048'],
            "*.reg_number" => ['required', 'string', 'max:255']
        ];
    }
}
