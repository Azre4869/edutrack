<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestasiRequest extends FormRequest
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
            'semester_id'               => 'required|exists:semester,id_semester',
            'prestasi'                  => 'required|array',
            'prestasi.*.nama_prestasi'  => 'nullable|string|max:100',
            'prestasi.*.jenis'          => 'nullable|in:Akademik,Non-Akademik',
            'prestasi.*.keterangan'     => 'nullable|string|max:255',
        ];
    }
}
