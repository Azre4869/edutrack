<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNilaiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user && $user->isOperator()) {
            return false;
        }
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
            'semester_id' => 'required|exists:semester,id_semester',
            'mapel_id'    => 'required|exists:mata_pelajaran,id_mapel',
            'nilai'       => 'required|array',
            'nilai.*.tp1' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp2' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp3' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp4' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp5' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp6' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp7' => 'nullable|numeric|min:0|max:100',
            'nilai.*.tp8' => 'nullable|numeric|min:0|max:100',
            'nilai.*.sas' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
