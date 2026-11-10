<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePresensiRequest extends FormRequest
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
            'tanggal'     => 'required|date',
            'semester_id' => 'required|exists:semester,id_semester',
            'kelas_id'    => 'nullable|exists:kelas,id_kelas',
            'status'      => 'required|array',
            'status.*'    => 'required|in:Hadir,Sakit,Izin,Alpha',
        ];
    }
}
