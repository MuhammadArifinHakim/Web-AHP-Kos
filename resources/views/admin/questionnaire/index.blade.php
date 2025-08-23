@extends('layouts.app')

@section('title', 'Data Kuesioner - Admin')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Data Kuesioner</h1>
        <div>
            <a href="{{ route('admin.questionnaire.create') }}" class="bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700 text-sm">
                Tambah Hasil Kuesioner
            </a>
        </div>
    </div>

    <div class="mb-4 flex items-center justify-between w-full">
        <form method="GET" action="{{ route('admin.questionnaire.index') }}" class="flex space-x-2 items-center">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari id / source..." class="border px-2 py-1 rounded text-sm" />

            <select name="campus_id" class="border px-2 py-1 rounded text-sm">
                <option value="">Semua Kampus</option>
                @foreach(\App\Models\Campus::all() as $campus)
                    <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                @endforeach
            </select>

            <select name="source" class="border px-2 py-1 rounded text-sm">
                <option value="">Semua Sumber</option>
                <option value="manual" @if(request('source')=='manual') selected @endif>Manual</option>
                <option value="excel" @if(request('source')=='excel') selected @endif>Excel</option>
            </select>

            <select name="cr_filter" class="border px-2 py-1 rounded text-sm">
                <option value="">Semua CR</option>
                <option value="consistent" @if(request('cr_filter')=='consistent') selected @endif>Hanya CR ≤ 0.10</option>
                <option value="inconsistent" @if(request('cr_filter')=='inconsistent') selected @endif>Hanya CR &gt; 0.10</option>
            </select>

            <button type="submit" class="bg-primary-600 text-white px-4 py-1 rounded hover:bg-primary-700 text-sm">Filter</button>
            <a href="{{ route('admin.questionnaire.index') }}" class="text-sm text-gray-600 underline ml-2">Reset</a>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-sm font-medium">
                    <th class="border px-2 py-2 text-center">No</th>
                    <th class="border px-2 py-2 text-center">#</th>
                    <th class="border px-2 py-2 text-center">Campus</th>

                    {{-- Pairwise 1..15 --}}
                    @for($i=1;$i<=15;$i++)
                        <th class="border px-2 py-2 text-center">P{{ $i }}</th>
                    @endfor

                    <th class="border px-2 py-2 text-center">CR</th>
                    <th class="border px-2 py-2 text-center">Source</th>
                    <th class="border px-2 py-2 text-center">Created</th>
                    <th class="border px-2 py-2 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="text-sm text-gray-700">
                @forelse($responses as $response)
                    @php
                        // normalize pairwise array to 0..14
                        $vals = $response->pairwise_values ?? [];
                        for ($k = 0; $k < 15; $k++) {
                            if (!array_key_exists($k, $vals)) $vals[$k] = null;
                        }

                        // compute the sequential number considering pagination
                        $no = $responses->firstItem() ? $responses->firstItem() + $loop->index : $loop->iteration;
                    @endphp

                    <tr class="border-b">
                        <td class="border px-2 py-1 text-center align-top">{{ $no }}</td>
                        <td class="border px-2 py-1 text-center align-top">{{ $response->id }}</td>
                        <td class="border px-2 py-1 text-center align-top">{{ $response->campus ? $response->campus->name : '-' }}</td>

                        @for($i=0;$i<15;$i++)
                            <td class="border px-2 py-1 text-center align-top">
                                {{ is_null($vals[$i]) ? '-' : (string)$vals[$i] }}
                            </td>
                        @endfor

                        <td class="border px-2 py-1 text-center align-top">
                            @if(!is_null($response->consistency_ratio))
                                <span class="{{ $response->consistency_ratio > 0.10 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                    {{ number_format((float)$response->consistency_ratio,4) }}
                                </span>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>

                        <td class="border px-2 py-1 text-center align-top">{{ $response->source }}</td>
                        <td class="border px-2 py-1 text-center align-top">{{ $response->created_at ? $response->created_at->format('Y-m-d H:i') : '-' }}</td>
                        <td class="border px-2 py-1 text-center align-top">
                            <form action="{{ route('admin.questionnaire.destroy', $response->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data response ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="border px-2 py-4 text-center text-gray-500">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $responses->withQueryString()->links() }}
    </div>
</div>
@endsection
