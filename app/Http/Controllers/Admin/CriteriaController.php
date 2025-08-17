<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use Illuminate\Http\Request;

class CriteriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $criteria = Criteria::with('subcriteria')->orderBy('order')->get();
        return view('admin.criteria.index', compact('criteria'));
        // return response()->json($criteria);
    }

    // public function create()
    // {
    //     return view('admin.criteria.create');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:criteria',
            'description' => 'nullable|string',
            'type' => 'required|in:cost,benefit',
            'order' => 'required|integer|min:1'
        ]);

        Criteria::create($request->all());

        return redirect()->route('admin.criteria.index')
                        ->with('success', 'Kriteria berhasil ditambahkan.');
    }

    public function edit(Criteria $criteria)
    {
        return view('admin.criteria.edit', compact('criteria'));
    }

    public function update(Request $request, Criteria $criteria)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:criteria,code,' . $criteria->id,
            'description' => 'nullable|string',
            'type' => 'required|in:cost,benefit',
            'order' => 'required|integer|min:1'
        ]);

        $criteria->update($request->all());

        return redirect()->route('admin.criteria.index')
                        ->with('success', 'Kriteria berhasil diperbarui.');
    }

    public function destroy(Criteria $criteria)
    {
        $criteria->delete();

        return redirect()->route('admin.criteria.index')
                        ->with('success', 'Kriteria berhasil dihapus.');
    }
}