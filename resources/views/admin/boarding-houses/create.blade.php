@extends('layouts.app')

@section('title', 'Tambah Data Kos - Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Data Kos</h1>
            <p class="text-sm text-gray-500">Masukkan data kos lengkap untuk keperluan perankingan AHP</p>
        </div>
        <a href="{{ route('admin.boarding-houses.index') }}" class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
            <!-- back icon -->
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali ke daftar
        </a>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <strong class="block font-medium mb-1">Terjadi kesalahan:</strong>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.boarding-houses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Basic info --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm">
                <div class="space-y-4">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Nama Kos <span class="text-red-500">*</span></span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"/>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Deskripsi</span>
                        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm">{{ old('description') }}</textarea>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Alamat</span>
                        <input type="text" name="address" value="{{ old('address') }}" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm"/>
                    </label>

                    <div class="grid grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Harga / Bulan (Rp) <span class="text-red-500">*</span></span>
                            <input type="number" name="price" value="{{ old('price') }}" required class="mt-1 block w-full rounded-md border-gray-200 shadow-sm"/>
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Gambar (opsional)</span>
                            <input type="file" name="image" accept="image/*" id="image-input" class="mt-1 block w-full text-sm text-gray-600"/>
                            <img id="image-preview" src="" alt="" class="mt-2 hidden w-48 h-32 object-cover rounded-md border"/>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Campus distances card --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-medium text-gray-900">Jarak ke Kampus</h3>
                    <button type="button" id="add-campus" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-1 text-sm font-medium text-white hover:bg-primary-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah
                    </button>
                </div>

                <div id="campus-rows" class="space-y-3">
                    <div class="campus-row grid grid-cols-12 gap-2 items-center">
                        <div class="col-span-7">
                            <select name="campuses[]" class="w-full rounded-md border-gray-200 px-3 py-2">
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="number" name="distances[]" step="0.1" placeholder="Jarak (m)" class="w-full rounded-md border-gray-200 px-3 py-2" />
                        </div>
                        <div class="col-span-1 text-right">
                            <button type="button" class="remove-row inline-flex items-center justify-center h-9 w-9 rounded-md bg-red-50 text-red-600 hover:bg-red-100">
                                &times;
                            </button>
                        </div>
                    </div>
                </div>

                <p class="mt-3 text-xs text-gray-500">Tambahkan baris kampus untuk menyimpan jarak ke tiap kampus. Jarak disimpan dalam meter.</p>
            </div>
        </div>

        {{-- Fasilitas Kamar & Bangunan --}}
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-medium text-gray-900">Fasilitas Kamar & Bangunan</h3>
                <span class="text-sm text-gray-500">Centang jika tersedia</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $c2 = ['kasur','lemari','meja','kursi','kipas_angin','ac','tv','wifi','kamar_mandi_dalam','dapur','parkiran','termasuk_listrik'];
                @endphp
                @foreach($c2 as $f)
                    <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                        <input type="checkbox" name="c2_{{ $f }}" id="c2_{{ $f }}" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                        <span class="text-sm text-gray-700">{{ ucwords(str_replace('_',' ',$f)) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Fasilitas Lingkungan --}}
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-medium text-gray-900">Fasilitas Lingkungan</h3>
                <span class="text-sm text-gray-500">Masukkan jarak ke fasilitas (meter)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php $c4 = ['warung','laundry','klinik','atm','minimarket','fotocopy','tempat_ibadah','pasar']; @endphp
                @foreach($c4 as $f)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ ucwords(str_replace('_',' ',$f)) }}</label>
                        <input type="number" step="0.1" name="c4_{{ $f }}" value="{{ old('c4_'.$f) }}" class="mt-1 block w-full rounded-md border-gray-200 px-3 py-2" placeholder="meter">
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Keamanan & Privasi --}}
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-medium text-gray-900">Keamanan & Privasi</h3>
                <span class="text-sm text-gray-500">Centang jika tersedia</span>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                    <input type="checkbox" name="c5_cctv" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                    <span class="text-sm text-gray-700">CCTV</span>
                </label>
                <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                    <input type="checkbox" name="c5_pagar" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                    <span class="text-sm text-gray-700">Pagar</span>
                </label>
                <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                    <input type="checkbox" name="c5_penjaga" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                    <span class="text-sm text-gray-700">Penjaga</span>
                </label>
            </div>
        </div>

        {{-- Peraturan Kos --}}
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Peraturan Kos</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jam Malam</label>
                    <select name="c6_jam_malam" class="mt-1 block w-full rounded-md border-gray-200 px-3 py-2">
                        <option value="ya" {{ old('c6_jam_malam')=='ya' ? 'selected' : '' }}>Ya</option>
                        <option value="tidak" {{ old('c6_jam_malam')=='tidak' ? 'selected' : '' }}>Tidak</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Mapping: Ya = 0, Tidak = 1</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Membawa Teman</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="c6_membawa_teman" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                            <span class="text-sm text-gray-700">Diizinkan</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Mapping: Ya = 1, Tidak = 0</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ketentuan Bayar</label>
                    <select name="c6_ketentuan_bayar" class="mt-1 block w-full rounded-md border-gray-200 px-3 py-2">
                        <option value="1">1 bulan</option>
                        <option value="3">3 bulan</option>
                        <option value="6">6 bulan</option>
                        <option value="12">12 bulan</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Mapping: 1 => 1, 3 => 0.75, 6 => 0.5, 12 => 0.25</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                    <input type="checkbox" name="c6_tamu_menginap" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                    <span class="text-sm text-gray-700">Tamu Menginap</span>
                </label>

                <label class="flex items-center gap-3 bg-gray-50 p-3 rounded-md">
                    <input type="checkbox" name="c6_hewan_peliharaan" value="1" class="h-4 w-4 text-primary-600 rounded"/>
                    <span class="text-sm text-gray-700">Hewan Peliharaan</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-white font-medium hover:bg-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Simpan Kos
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Add/remove campus rows
    document.getElementById('add-campus').addEventListener('click', function(){
        const container = document.getElementById('campus-rows');
        const proto = container.querySelector('.campus-row');
        const clone = proto.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => i.value = '');
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        container.appendChild(clone);
    });

    document.getElementById('campus-rows').addEventListener('click', function(e){
        if (e.target && e.target.closest('.remove-row')) {
            const rows = document.querySelectorAll('.campus-row');
            const btn = e.target.closest('.remove-row');
            if (rows.length > 1) {
                btn.closest('.campus-row').remove();
            } else {
                // clear values
                btn.closest('.campus-row').querySelectorAll('input,select').forEach(el => {
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = '';
                });
            }
        }
    });

    // Image preview
    const imgInput = document.getElementById('image-input');
    const imgPreview = document.getElementById('image-preview');
    if (imgInput) {
        imgInput.addEventListener('change', function(e){
            const file = e.target.files[0];
            if (!file) { imgPreview.classList.add('hidden'); imgPreview.src = ''; return; }
            const reader = new FileReader();
            reader.onload = function(ev) {
                imgPreview.src = ev.target.result;
                imgPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
@endpush
@endsection
