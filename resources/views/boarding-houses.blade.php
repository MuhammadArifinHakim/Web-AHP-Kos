@extends('layouts.app')

@section('title', 'Daftar Kos - ' . $campus->name)

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
                <li>
                    <span class="text-gray-500">{{ $campus->name }}</span>
                </li>
            </ol>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Daftar Kos di {{ $campus->name }}
        </h1>
        <p class="text-gray-600">
            Pilih kos yang ingin Anda evaluasi, kemudian klik tombol "Proses AHP" untuk melihat ranking
        </p>
    </div>

    <!-- Boarding Houses Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($boardingHouses as $kos)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
            <div class="h-48 bg-gradient-to-br from-primary-400 to-secondary-400 relative">
                @if($kos->image)
                    <img src="{{ asset('storage/' . $kos->image) }}" alt="{{ $kos->name }}" class="w-full h-full object-cover">
                @endif
                <div class="absolute top-4 right-4">
                    <span class="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-semibold text-gray-900">
                        Rp {{ number_format($kos->price, 0, ',', '.') }}/bulan
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $kos->name }}</h3>
                
                @if($kos->address)
                    <div class="flex items-center text-gray-600 mb-3">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="text-sm">{{ $kos->address }}</span>
                    </div>
                @endif
                
                <div class="flex items-center text-gray-600 mb-4">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-sm">{{ $kos->getDistanceToCampus($campus->id) }}m dari kampus</span>
                </div>
                
                @if($kos->description)
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $kos->description }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Process AHP Button -->
    <div class="text-center">
        @if($weightMethod === 'manual')
            <a href="{{ route('pairwise') }}?campus_id={{ $campus->id }}" 
               class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 8l2 2 4-4"></path>
                </svg>
                Isi Pairwise Comparison
            </a>
        @else
            <form action="{{ route('process.system') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="campus_id" value="{{ $campus->id }}">
                <button type="submit" class="inline-flex items-center bg-secondary-600 hover:bg-secondary-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    Proses AHP dengan Rekomendasi Sistem
                </button>
            </form>
        @endif
    </div>
</div>
@endsection