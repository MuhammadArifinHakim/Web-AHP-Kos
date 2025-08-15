@extends('layouts.app')

@section('title', 'Beranda - AHP Kos Selection')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="text-center mb-16">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Sistem Pendukung Keputusan
            <span class="text-primary-600">Pemilihan Kos</span>
        </h1>
        <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Temukan kos terbaik untuk kebutuhan Anda menggunakan metode Analytical Hierarchy Process (AHP) 
            dengan mempertimbangkan berbagai kriteria penting.
        </p>
    </div>

    <!-- Method Selection -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6 text-center">
            Pilih Metode Penentuan Bobot Kriteria
        </h2>
        
        <form action="{{ route('select.campus') }}" method="POST" id="methodForm">
            @csrf
            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <!-- Manual Method -->
                <div class="border border-gray-200 rounded-lg p-6 hover:border-primary-300 transition-colors cursor-pointer" onclick="selectMethod('manual')">
                    <input type="radio" name="weight_method" value="manual" id="manual" class="sr-only">
                    <label for="manual" class="cursor-pointer">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Manual (Pairwise Comparison)</h3>
                        </div>
                        <p class="text-gray-600 leading-relaxed">
                            Anda akan melakukan perbandingan berpasangan antar kriteria secara manual. 
                            Metode ini memberikan hasil yang lebih personal sesuai preferensi Anda.
                        </p>
                        <div class="mt-4 text-sm text-primary-600 font-medium">
                            ✓ Hasil sesuai preferensi personal<br>
                            ✓ Kontrol penuh atas bobot kriteria<br>
                            ✓ Validasi consistency ratio
                        </div>
                    </label>
                </div>

                <!-- System Recommendation -->
                <div class="border border-gray-200 rounded-lg p-6 hover:border-secondary-300 transition-colors cursor-pointer" onclick="selectMethod('system')">
                    <input type="radio" name="weight_method" value="system" id="system" class="sr-only">
                    <label for="system" class="cursor-pointer">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-secondary-100 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Rekomendasi Sistem</h3>
                        </div>
                        <p class="text-gray-600 leading-relaxed">
                            Menggunakan bobot kriteria dari rata-rata hasil kuesioner 211 mahasiswa. 
                            Metode ini lebih cepat dan berbasis data empiris.
                        </p>
                        <div class="mt-4 text-sm text-secondary-600 font-medium">
                            ✓ Berdasarkan data 211 mahasiswa<br>
                            ✓ Proses lebih cepat<br>
                            ✓ Hasil berbasis empiris
                        </div>
                    </label>
                </div>
            </div>

            <!-- Campus Selection -->
            <div class="mb-8" id="campusSelection" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pilih Kampus</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($campuses as $campus)
                        <div class="border border-gray-200 rounded-lg p-6 hover:border-primary-300 transition-colors cursor-pointer" onclick="selectCampus({{ $campus->id }})">
                            <input type="radio" name="campus_id" value="{{ $campus->id }}" id="campus_{{ $campus->id }}" class="sr-only">
                            <label for="campus_{{ $campus->id }}" class="cursor-pointer">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-xl">{{ $campus->code }}</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 mb-2">{{ $campus->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $campus->description }}</p>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Continue Button -->
            <div class="text-center" id="continueButton" style="display: none;">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                    Lanjutkan ke Pemilihan Kos
                </button>
            </div>
        </form>
    </div>

    <!-- Features Section -->
    <div class="grid md:grid-cols-3 gap-8 mb-12">
        <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Metode AHP</h3>
            <p class="text-gray-600">Menggunakan Analytical Hierarchy Process untuk pengambilan keputusan yang akurat</p>
        </div>
        
        <div class="text-center">
            <div class="w-16 h-16 bg-secondary-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Multi Kriteria</h3>
            <p class="text-gray-600">Evaluasi berdasarkan 6 kriteria utama: lokasi, harga, fasilitas, dan lainnya</p>
        </div>
        
        <div class="text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Hasil Akurat</h3>
            <p class="text-gray-600">Ranking kos yang tepat sesuai preferensi dan kebutuhan Anda</p>
        </div>
    </div>
</div>

<script>
function selectMethod(method) {
    // Update radio button
    document.getElementById(method).checked = true;
    
    // Update visual selection
    document.querySelectorAll('[onclick^="selectMethod"]').forEach(el => {
        el.classList.remove('ring-2', 'ring-primary-500', 'border-primary-500');
        el.classList.add('border-gray-200');
    });
    
    event.currentTarget.classList.remove('border-gray-200');
    if (method === 'manual') {
        event.currentTarget.classList.add('ring-2', 'ring-primary-500', 'border-primary-500');
    } else {
        event.currentTarget.classList.add('ring-2', 'ring-secondary-500', 'border-secondary-500');
    }
    
    // Show campus selection
    document.getElementById('campusSelection').style.display = 'block';
}

function selectCampus(campusId) {
    // Update radio button
    document.getElementById('campus_' + campusId).checked = true;
    
    // Update visual selection
    document.querySelectorAll('[onclick^="selectCampus"]').forEach(el => {
        el.classList.remove('ring-2', 'ring-primary-500', 'border-primary-500');
        el.classList.add('border-gray-200');
    });
    
    event.currentTarget.classList.remove('border-gray-200');
    event.currentTarget.classList.add('ring-2', 'ring-primary-500', 'border-primary-500');
    
    // Show continue button
    document.getElementById('continueButton').style.display = 'block';
}
</script>
@endsection