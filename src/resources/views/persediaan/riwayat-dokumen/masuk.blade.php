@extends('persediaan.partials.header')

@section('title', 'Riwayat Dokumen BAST Masuk')

@section('content')
    @include('persediaan.riwayat-dokumen.partials.styles')

    @include('persediaan.riwayat-dokumen.partials.topbar', ['activeTab' => 'masuk'])

    <div class="table-responsive bg-white rounded-4 shadow-sm p-3 border-0">
        @include('persediaan.riwayat-dokumen.partials.table-masuk', ['masukDocs' => $masukDocs])
    </div>

    @stack('modals')
@endsection

@push('scripts')
    @include('persediaan.riwayat-dokumen.partials.scripts')
@endpush
