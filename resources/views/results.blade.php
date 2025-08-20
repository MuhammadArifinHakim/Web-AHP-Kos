@extends('layouts.app')

@section('title', 'Hasil Ranking - ' . $campus->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <span class="text-gray-500">Hasil Ranking</span>
                </li>
            </ol>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Hasil Ranking Kos - {{ $campus->name }}
        </h1>
        <p class="text-gray-600">
            Ranking berdasarkan metode AHP dengan {{ $method === 'manual' ? 'bobot manual' : 'rekomendasi sistem' }}
        </p>
    </div>

    <!-- Method Info -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Informasi Perhitungan</h2>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $method === 'manual' ? 'bg-primary-100 text-primary-800' : 'bg-secondary-100 text-secondary-800' }}">
                {{ $method === 'manual' ? 'Manual Pairwise' : 'Rekomendasi Sistem' }}
            </span>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h3 class="font-medium text-gray-900 mb-3">Bobot Kriteria:</h3>
                <div class="space-y-2">
                    @foreach($criteria as $index => $criterion)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">{{ $criterion->name }}</span>
                            <div class="flex items-center">
                                <span class="font-medium text-gray-900 mr-2">{{ number_format($weights[$index] * 100, 1) }}%</span>
                                <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 transition-all duration-500" style="width: {{ $weights[$index] * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-900 mb-3">Detail Validasi:</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-700">Consistency Ratio:</span>
                        <span class="font-medium {{ $consistencyRatio <= 0.10 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($consistencyRatio, 4) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-700">Status Konsistensi:</span>
                        <span class="font-medium {{ $consistencyRatio <= 0.10 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $consistencyRatio <= 0.10 ? 'Konsisten' : 'Tidak Konsisten' }}
                        </span>
                    </div>
                    <!-- <div class="flex justify-between">
                        <span class="text-gray-700">Jumlah Alternatif:</span>
                        <span class="font-medium text-gray-900">{{ count($ranking) }} Kos</span>
                    </div> -->
                    <div class="flex justify-between">
                        <span class="text-gray-700">Jumlah Alternatif:</span>
                        <span class="font-medium text-gray-900">{{ count($ranking) }} Kos</span>
                    </div>

                    @if($method === 'system')
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-gray-700">Jumlah Responden (digunakan):</span>
                            <span class="font-medium text-gray-900">{{ $respondentCount ?? 0 }} respon</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking Results -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Ranking Kos</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ranking</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jarak</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Skor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ranking as $index => $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 {{ $item['rank'] <= 3 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : 'bg-gray-400' }} rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ $item['rank'] }}</span>
                                    </div>
                                    @if($item['rank'] == 1)
                                        <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium">
                                            Terbaik
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $item['kos']['name'] }}</div>
                                @if($item['kos']['address'])
                                    <div class="text-sm text-gray-500">{{ $item['kos']['address'] }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp {{ number_format($item['kos']['price'], 0, ',', '.') }}/bulan
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item['kos']->getDistanceToCampus($campus->id) }}m
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-lg font-bold text-gray-900 mr-2">
                                        {{ number_format($item['score'], 3) }}
                                    </span>
                                    <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary-500 transition-all duration-500" style="width: {{ $item['score'] * 100 }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="toggleDetails({{ $index }})" class="text-primary-600 hover:text-primary-900 font-medium">
                                    Detail Skor
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Detail Row -->
                        <tr id="details-{{ $index }}" class="hidden bg-gray-50">
                            <td colspan="6" class="px-6 py-4">
                                <div class="grid md:grid-cols-3 gap-4">
                                    @foreach($criteria as $criteriaIndex => $criterion)
                                        @if(isset($item['criteria_scores'][$criterion->code]))
                                            <div class="bg-white rounded-lg p-4">
                                                <h4 class="font-medium text-gray-900 mb-2">{{ $criterion->name }}</h4>
                                                <div class="space-y-1">
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600">Skor Mentah:</span>
                                                        <span class="font-medium">{{ number_format($item['criteria_scores'][$criterion->code]['raw_score'], 3) }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600">Bobot:</span>
                                                        <span class="font-medium">{{ number_format($weights[$criteriaIndex], 3) }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-sm border-t pt-1">
                                                        <span class="text-gray-600">Skor Berbobot:</span>
                                                        <span class="font-bold text-primary-600">{{ number_format($item['criteria_scores'][$criterion->code]['weighted_score'], 3) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-8 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-800 font-medium">
            ← Analisis Ulang
        </a>
        
        <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            Cetak Hasil
        </button>
    </div>
</div>

<script>
function toggleDetails(index) {
    const detailsRow = document.getElementById(`details-${index}`);
    detailsRow.classList.toggle('hidden');
}
</script>
@endsection