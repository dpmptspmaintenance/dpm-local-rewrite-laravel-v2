@extends('bangkit.partials.header')

@section('title', 'Ubah User Bangkit')

@section('content')
    <h2 class="fw-bold mb-4">Ubah User Bangkit</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('bangkit.user.update', $user->id) }}" method="POST" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Nama</label>
                    <input required type="text" name="nama" class="form-control" value="{{ old('nama', $user->nama) }}">
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Email (Google)</label>
                    <input type="text" class="form-control" value="{{ $user->email }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Bidang</label>
                    <input type="text" name="bidang" class="form-control" list="bidangList" value="{{ old('bidang', $user->bidang) }}" placeholder="Ketik atau pilih bidang">
                    <datalist id="bidangList">
                        @foreach ($bidangs as $b)
                            <option value="{{ $b }}"></option>
                        @endforeach
                    </datalist>
                    <div class="form-text">Dipakai untuk scoping data SDIA. Harus sama dengan salah satu nama bidang master.</div>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Role</label>
                    <select name="role" class="form-select" required>
                        @foreach ($roles as $id => $label)
                            <option value="{{ $id }}" @selected((int) old('role', $user->role) === $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_admin_bangkit" value="0">
                        <input class="form-check-input" type="checkbox" name="is_admin_bangkit" value="1" id="isAdminBangkit" @checked(old('is_admin_bangkit', $user->is_admin_bangkit))>
                        <label class="form-check-label fw-semibold" for="isAdminBangkit">Jadikan Admin Bangkit (akses penuh modul)</label>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('bangkit.user.daftar') }}" class="btn btn-danger">Kembali</a>
                    <button class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
