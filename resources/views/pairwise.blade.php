@extends('layouts.app')

@section('title', 'Pairwise Comparison - ' . $campus->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="text-sm breadcrumbs mb-4">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="{{ route('home') }}" class="text-primary-600 hover:text-primary-800">Beranda</a>
                    <svg class="fill-current w-3 h-3 mx-3" viewBox="0 0 320 512">
                        <path d="m285.476 272.971c4.686 4.686 4.686 12.284 0 16.971l-128 128c-4.686 4.686-12.284 4.686-16.971 0l-128-128c-4.686-4.686-4.686-12.284 0-16.971s12.284-4.686 16.971 0l119.514 119.514 119.514-119.514c4.686-4.686 12.284-4.686 16.971 0z"/>
                    </svg>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-500">{{ $campus->name }}</span>
                    <svg class="fill-current w-3 h-3 mx-3" viewBox="0 0 320 512">
                        <path d="m285.476 272.971c4.686 4.686 4.686 12.284 0 16.971l-128 128c-4.686 4.686-12.284 4.686-16.971 0l-128-128c-4.686-4.686-4.686-12.284 0-16.971s12.284-4.686 16.971 0l119.514 119.514 119.514-119.514c4.686-4.686 12.284-4.686 16.971 0z"/>
                    </svg>
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

    <!-- Pairwise Comparison Form -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        @if($errors->has('consistency'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $errors->first('consistency') }}
                </div>
            </div>
        @endif

        <form action="{{ route('process.pairwise') }}" method="POST" id="pairwiseForm">
            @csrf
            <input type="hidden" name="campus_id" value="{{ $campus->id }}">
            
            <div class="space-y-6">
                @foreach($questions as $index => $question)
                    <div class="border border-gray-200 rounded-lg p-6 hover:border-primary-300 transition-colors">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">
                            {{ $index + 1 }}. {{ $question['text'] }}
                        </h4>
                        
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 w-1/4">
                                <span class="font-medium">{{ $question['criteria1'] }}</span><br>
                                lebih penting
                            </div>
                            
                            <div class="flex-1 px-8">
                                <div class="flex items-center space-x-2">
                                    @foreach([9, 7, 5, 3, 1, 1/3, 1/5, 1/7, 1/9] as $key => $value)
                                        <label class="flex flex-col items-center cursor-pointer">
                                            <input type="radio" name="pairwise[{{ $index }}]" 
                                                   value="{{ $value }}" 
                                                   class="sr-only"
                                                   {{ old("pairwise.$index") == $value ? 'checked' : '' }}
                                                   onchange="updateSelection(this, {{ $index }})">
                                            <div class="w-10 h-10 border-2 border-gray-300 rounded-full flex items-center justify-center text-sm font-medium hover:border-primary-500 transition-colors radio-button">
                                                @if($value >= 1)
                                                    {{ intval($value) }}
                                                @else
                                                    1/{{ intval(1/$value) }}
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-2">
                                    <span>Sangat Lebih</span>
                                    <span>Sama</span>
                                    <span>Sangat Lebih</span>
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600 w-1/4 text-right">
                                <span class="font-medium">{{ $question['criteria2'] }}</span><br>
                                lebih penting
                            </div>
                        </div>
                        
                        @error("pairwise.$index")
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-between items-center">
                <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-800 font-medium">
                    ← Kembali ke Beranda
                </a>
                
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                    Proses AHP
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSelection(radio, questionIndex) {
    // Remove selection from all radio buttons in this question
    const questionDiv = radio.closest('.border');
    questionDiv.querySelectorAll('.radio-button').forEach(btn => {
        btn.classList.remove('bg-primary-600', 'text-white', 'border-primary-600');
        btn.classList.add('border-gray-300');
    });
    
    // Add selection to clicked radio button
    const selectedButton = radio.nextElementSibling;
    selectedButton.classList.remove('border-gray-300');
    selectedButton.classList.add('bg-primary-600', 'text-white', 'border-primary-600');
}

// Initialize form with old values
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const selectedButton = radio.nextElementSibling;
        selectedButton.classList.remove('border-gray-300');
        selectedButton.classList.add('bg-primary-600', 'text-white', 'border-primary-600');
    });
});
</script>
@endsection