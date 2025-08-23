@extends('layouts.app')

@section('title', 'Input Kuesioner Mahasiswa')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Input Hasil Kuesioner Mahasiswa</h1>

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

    <form method="POST" action="{{ route('admin.questionnaire.store') }}">
        @csrf
        <input type="hidden" name="input_type" value="manual">

        <!-- Pilih Kampus -->
        <div class="mb-4">
            <label for="campus_id" class="block font-semibold mb-1">Kampus</label>
            <select name="campus_id" id="campus_id" class="border rounded px-3 py-2 w-full" required>
                <option value="">-- Pilih Kampus --</option>
                @foreach($campuses as $campus)
                    <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Pairwise Questions -->
        <div class="mb-4">
            <label class="block font-semibold mb-2">Pairwise Comparison (1–9)</label>
            @foreach($questions as $key => $question)
                <div class="mb-2">
                    <label class="block mb-1">{{ $question }}</label>
                    <input type="number"
                           name="pairwise[{{ $key }}]"
                           value="{{ old('pairwise.'.$key) }}"
                           min="1"
                           max="9"
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
@endsection
