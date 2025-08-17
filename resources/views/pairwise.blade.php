@extends('layouts.app')

@section('title', 'Pairwise Comparison - ' . ($campus->name ?? ''))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="text-sm breadcrumbs mb-4">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="{{ route('home') }}" class="text-primary-600 hover:text-primary-800">Beranda</a>
                    <svg class="fill-current w-3 h-3 mx-3" viewBox="0 0 320 512"><path d="m285.476 272.971c4.686 4.686 4.686 12.284 0 16.971l-128 128c-4.686 4.686-12.284 4.686-16.971 0l-128-128c-4.686-4.686-4.686-12.284 0-16.971s12.284-4.686 16.971 0l119.514 119.514 119.514-119.514c4.686-4.686 12.284-4.686 16.971 0z"/></svg>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-500">{{ $campus->name ?? '—' }}</span>
                    <svg class="fill-current w-3 h-3 mx-3" viewBox="0 0 320 512"><path d="m285.476 272.971c4.686 4.686 4.686 12.284 0 16.971l-128 128c-4.686 4.686-12.284 4.686-16.971 0l-128-128c-4.686-4.686-4.686-12.284 0-16.971s12.284-4.686 16.971 0l119.514 119.514 119.514-119.514c4.686-4.686 12.284-4.686 16.971 0z"/></svg>
                </li>
                <li>
                    <span class="text-gray-500">Pairwise Comparison</span>
                </li>
            </ol>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Perbandingan Berpasangan (Pairwise Comparison)
        </h1>
        <p class="text-gray-600">
            Bandingkan tingkat kepentingan antar kriteria menggunakan skala 1-9 (Skala Saaty)
        </p>
    </div>

    <!-- Skala Saaty Guide -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Panduan Skala Saaty:</h3>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="flex justify-between py-1"><span class="font-medium">1:</span><span>Sama penting</span></div>
                <div class="flex justify-between py-1"><span class="font-medium">3:</span><span>Sedikit lebih penting</span></div>
                <div class="flex justify-between py-1"><span class="font-medium">5:</span><span>Lebih penting</span></div>
                <div class="flex justify-between py-1"><span class="font-medium">7:</span><span>Sangat lebih penting</span></div>
                <div class="flex justify-between py-1"><span class="font-medium">9:</span><span>Mutlak lebih penting</span></div>
            </div>
            <div class="text-blue-700">
                <p class="font-medium mb-2">Nilai tengah (2, 4, 6, 8) dapat digunakan untuk tingkat kepentingan di antara skala utama.</p>
                <p class="text-xs">Contoh: Jika Lokasi lebih penting dari Harga, pilih nilai 3-9 sesuai tingkat kepentingannya.</p>
            </div>
        </div>
    </div>

    <!-- Pairwise Comparison Form (campus already selected) -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-2 mb-4 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- <form method="POST" action="{{ route('admin.questionnaire.store') }}"> -->
        <form method="POST" action="{{ route('process.pairwise') }}">
            @csrf
            <input type="hidden" name="input_type" value="manual">
            {{-- Campus id passed as hidden because user already selected campus before opening this page --}}
            <input type="hidden" name="campus_id" value="{{ $campus->id }}">

            <!-- Pairwise Questions -->
            <div class="mb-4">
                <label class="block font-semibold mb-2">Pairwise Comparison (1–9)</label>
                @foreach($questions as $key => $question)
                    <div class="mb-2">
                        <label class="block mb-1">{{ is_array($question) ? ($question['text'] ?? '') : $question }}</label>
                        <input type="number"
                               name="pairwise[{{ $key }}]"
                               value="{{ old('pairwise.'.$key) }}"
                               min="1"
                               max="9"
                               step="1"
                               class="border rounded px-2 py-1 w-24"
                               required>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">
                Simpan
            </button>
        </form>
    </div>
</div>
@endsection
