@extends('layouts.app')

@section('title', 'Data Kos - Admin')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Judul dan tombol tambah --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Data Kos</h1>
    </div>

    <div class="mb-4 flex items-center justify-between w-full">
        <!-- Form pencarian -->
        <form method="GET" action="{{ route('admin.boarding-houses.index') }}" class="flex space-x-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kos..." class="border px-2 py-1 rounded">
            <select name="campus_id" class="border px-2 py-1 rounded">
                <option value="">Semua Kampus</option>
                @foreach(App\Models\Campus::all() as $campus)
                    <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-primary-600 text-white px-4 py-1 rounded hover:bg-primary-700">Cari</button>
        </form>

        <!-- Tombol tambah Kos di ujung kanan layar -->
        <a href="{{ route('admin.boarding-houses.create') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded font-medium">
            Tambah Kos
        </a>
    </div>


    <!-- {{-- Hasil pencarian --}}
    @if(request()->filled('search') || request()->filled('campus_id'))
        <div class="mb-4 border rounded p-2">
            <h2 class="font-bold mb-2">Hasil Pencarian:</h2>
            <ul>
                @forelse($boardingHouses as $house)
                    @foreach($house->campuses as $campus)
                        @if(!request('campus_id') || request('campus_id') == $campus->id)
                            <li class="flex justify-between border-b py-1">
                                <span>{{ $house->name }} - {{ $campus->name }}</span>
                                <span>Rp {{ number_format($house->price, 0, ',', '.') }}</span>
                            </li>
                        @endif
                    @endforeach
                @empty
                    <li>Tidak ada kos yang ditemukan.</li>
                @endforelse
            </ul>
        </div>
    @endif -->

    {{-- Tabel Data Kos --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-sm font-medium">
                    <th class="border px-2 py-2">Kampus</th>
                    <th class="border px-2 py-2">Nama Kos</th>
                    <th class="border px-2 py-2">Jarak ke Kampus</th>
                    <th class="border px-2 py-2">Harga/Bulan (Rp)</th>
                    <th class="border px-2 py-2">Kasur</th>
                    <th class="border px-2 py-2">Lemari</th>
                    <th class="border px-2 py-2">Meja</th>
                    <th class="border px-2 py-2">Kursi</th>
                    <th class="border px-2 py-2">Kipas Angin</th>
                    <th class="border px-2 py-2">AC</th>
                    <th class="border px-2 py-2">TV</th>
                    <th class="border px-2 py-2">WIFI</th>
                    <th class="border px-2 py-2">Kamar Mandi Dalam</th>
                    <th class="border px-2 py-2">Dapur</th>
                    <th class="border px-2 py-2">Parkiran</th>
                    <th class="border px-2 py-2">Termasuk Listrik</th>
                    <th class="border px-2 py-2">Warung</th>
                    <th class="border px-2 py-2">Laundry</th>
                    <th class="border px-2 py-2">&nbsp;&nbsp;Klinik&nbsp;&nbsp;</th>
                    <th class="border px-2 py-2">&nbsp;&nbsp;&nbsp;ATM&nbsp;&nbsp;&nbsp;</th>
                    <th class="border px-2 py-2">MiniMarket</th>
                    <th class="border px-2 py-2">Fotocopy</th>
                    <th class="border px-2 py-2">Tempat Ibadah</th>
                    <th class="border px-2 py-2">&nbsp;&nbsp;Pasar&nbsp;&nbsp;</th>
                    <th class="border px-2 py-2">CCTV</th>
                    <th class="border px-2 py-2">Pagar</th>
                    <th class="border px-2 py-2">Penjaga</th>
                    <th class="border px-2 py-2">Jam Malam</th>
                    <th class="border px-2 py-2">Membawa Teman</th>
                    <th class="border px-2 py-2">Ketentuan Bayar</th>
                    <th class="border px-2 py-2">Tamu Menginap</th>
                    <th class="border px-2 py-2">Hewan Peliharaan</th>
                    <th class="border px-2 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700">
                @foreach($boardingHouses as $house)
                    @php
                        $criteria = collect($house->criteriaValues)->keyBy('criteria_id')->map(function($item) {
                            return $item->values ?? [];
                        });
                    @endphp
                    @foreach($house->campuses as $campus)
                        {{-- Jika filter campus_id aktif, tampilkan hanya kos yang sesuai --}}
                        @if(!request('campus_id') || request('campus_id') == $campus->id)
                        <tr class="border-b">
                            <td class="border px-2 py-1">{{ $campus->name }}</td>
                            <td class="border px-2 py-1">{{ $house->name }}</td>
                            <td class="border px-2 py-1">{{ $campus->pivot->distance }} m</td>
                            <td class="border px-2 py-1">Rp {{ number_format($house->price, 0, ',', '.') }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['kasur'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['lemari'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['meja'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['kursi'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['kipas_angin'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['ac'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['tv'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['wifi'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['kamar_mandi_dalam'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['dapur'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['parkiran'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[2]['termasuk_listrik'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['warung']) ? $criteria[4]['warung'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['laundry']) ? $criteria[4]['laundry'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['klinik']) ? $criteria[4]['klinik'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['atm']) ? $criteria[4]['atm'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['minimarket']) ? $criteria[4]['minimarket'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['fotocopy']) ? $criteria[4]['fotocopy'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['tempat_ibadah']) ? $criteria[4]['tempat_ibadah'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ isset($criteria[4]['pasar']) ? $criteria[4]['pasar'] . ' m' : '-' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[5]['cctv'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[5]['pagar'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[5]['penjaga'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[6]['jam_malam'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[6]['membawa_teman'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">
                                @php
                                    $bayar = $criteria[6]['ketentuan_bayar'] ?? null;
                                    $bayarText = '-';
                                    if ($bayar === 1) $bayarText = '1 BULAN';
                                    elseif ($bayar === 0.75) $bayarText = '3 BULAN';
                                    elseif ($bayar === 0.5) $bayarText = '6 BULAN';
                                    elseif ($bayar === 0.25) $bayarText = '12 BULAN';
                                @endphp
                                {{ $bayarText }}
                            </td>
                            <td class="border px-2 py-1">{{ ($criteria[6]['tamu_menginap'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">{{ ($criteria[6]['hewan_peliharaan'] ?? 0) ? 'YA' : 'TIDAK' }}</td>
                            <td class="border px-2 py-1">
                                <a href="{{ route('admin.boarding-houses.edit', $house) }}" class="text-primary-600 hover:text-primary-900 text-xs">Edit</a>
                                <form action="{{ route('admin.boarding-houses.destroy', $house) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data kos ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
