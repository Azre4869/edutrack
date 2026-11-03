<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatatanRequest extends FormRequest
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
            'semester_id'                => 'required|exists:semester,id_semester',
            'catatan'                    => 'required|array',
            'catatan.*.sikap_spiritual'  => 'nullable|string',
            'catatan.*.sikap_sosial'     => 'nullable|string',
            'catatan.*.catatan_wali'     => 'nullable|string',
            'catatan.*.status_kenaikan'  => 'nullable|in:Naik,Tinggal',
        ];
    }
}
