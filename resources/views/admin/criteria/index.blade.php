@extends('layouts.app')

@section('title', 'Kelola Kriteria - Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Kelola Kriteria</h1>
            <p class="text-gray-600">Daftar kriteria dan subkriteria untuk evaluasi kos</p>
        </div>
        <a href="{{ route('admin.criteria.create') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            Tambah Kriteria
        </a>
    </div>

    <!-- Criteria List -->
    <div class="space-y-6">
        @foreach($criteria as $criterion)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $criterion->name }}</h3>
                        <p class="text-gray-600 mb-2">{{ $criterion->description }}</p>
                        <div class="flex items-center space-x-4 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $criterion->type === 'benefit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $criterion->type === 'benefit' ? 'Benefit (Lebih Besar Lebih Baik)' : 'Cost (Lebih Kecil Lebih Baik)' }}
                            </span>
                            <span class="text-gray-500">Kode: {{ $criterion->code }}</span>
                            <span class="text-gray-500">Urutan: {{ $criterion->order }}</span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.criteria.edit', $criterion) }}" class="text-primary-600 hover:text-primary-900 text-sm font-medium">
                            Edit
                        </a>
                        <form action="{{ route('admin.criteria.destroy', $criterion) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kriteria ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>

                @if($criterion->subcriteria->count() > 0)
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="font-medium text-gray-900 mb-3">Subkriteria:</h4>
                        <div class="grid md:grid-cols-3 gap-3">
                            @foreach($criterion->subcriteria as $subcriteria)
                                <div class="border border-gray-200 rounded-lg p-3">
                                    <h5 class="font-medium text-gray-900 text-sm">{{ $subcriteria->name }}</h5>
                                    <div class="flex justify-between items-center text-xs text-gray-500 mt-1">
                                        <span>{{ $subcriteria->code }}</span>
                                        <span class="px-2 py-1 rounded-full bg-gray-100">{{ $subcriteria->type }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection