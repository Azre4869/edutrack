<?php

namespace App\Http\Controllers;

use App\Models\KriteriaAnalisis;
use Illuminate\Http\Request;

class KriteriaAnalisisController extends Controller
{
    public function index()
    {
        $kriteriaList = KriteriaAnalisis::orderBy('id_kriteria')->get();
        return view('early-warning.kriteria', compact('kriteriaList'));
    }

    public function update(Request $request, $id)
    {
        $kriteria = KriteriaAnalisis::findOrFail($id);

        $data = $request->validate([
            'nama_kriteria' => 'required|string|max:100',
            'nilai_ambang' => 'required|numeric|min:0',
            'bobot' => 'required|numeric|min:0.1|max:5',
            'parameter' => 'nullable|string|max:255',
            'aktif' => 'nullable|boolean',
        ]);

        $data['aktif'] = $request->has('aktif') ? 1 : 0;

        $kriteria->update($data);

        return redirect()->route('operator.early-warning.kriteria')
            ->with('success', "Kriteria \"{$kriteria->nama_kriteria}\" berhasil diperbarui.");
    }
}
