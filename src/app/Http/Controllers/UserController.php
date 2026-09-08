<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private const ROLES = [
        1 => 'Superadmin',
        2 => 'Kadin',
        3 => 'Sekdin',
        4 => 'Kasubag',
        5 => 'Bendahara',
        6 => 'User',
        7 => 'Dinas Luar (App Local)',
        8 => 'Dinas Luar (App Khusus)',
    ];

    private const DAFTAR_MENU = [
        'rapat_kita' => 'Rapat Kita',
        'barang_kita' => 'Barang Kita',
        'data_kita' => 'Data Kita',
        'sikenut' => 'Sikenut',
        'botman' => 'Botman',
        'botman_manager' => 'Bot Manager',
        'siperdafit' => 'Siperdafit',
    ];

    private function abortIfNotAdmin(): void
    {
        abort_if((int) (Auth::user()->role ?? 0) !== 1, 403);
    }

    public function index()
    {
        $this->abortIfNotAdmin();

        return view('user.index', [
            'roles' => self::ROLES,
            'daftarMenu' => self::DAFTAR_MENU,
        ]);
    }

    public function getUsers(Request $request)
    {
        $this->abortIfNotAdmin();

        $query = User::query()->select('id', 'nama', 'role', 'bidang', 'email', 'is_aktif', 'shared_pages');

        if ($request->filled('filter_aktif')) {
            $query->where('is_aktif', (int) $request->input('filter_aktif'));
        }

        if ($request->input('filter_gmail') === '1') {
            $query->whereNotNull('email')->where('email', '!=', '');
        } elseif ($request->input('filter_gmail') === '0') {
            $query->where(function ($q) {
                $q->whereNull('email')->orWhere('email', '');
            });
        }

        if ($request->filled('filter_role')) {
            $query->where('role', (int) $request->input('filter_role'));
        }

        if ($request->filled('filter_bidang')) {
            $query->where('bidang', 'like', '%'.$request->input('filter_bidang').'%');
        }

        $users = $query->orderByDesc('id')->get();

        return view('user.partials.rows', [
            'users' => $users,
            'roles' => self::ROLES,
        ]);
    }

    public function addUser(Request $request)
    {
        $this->abortIfNotAdmin();

        $nama = trim((string) $request->input('nama', ''));
        $email = trim((string) $request->input('email', ''));
        $role = (int) $request->input('role', 6);
        $bidang = trim((string) $request->input('bidang', ''));
        $isAktif = (int) $request->input('isaktif', 1);

        if ($nama === '' || $email === '' || $role === 0 || $bidang === '') {
            return response()->json(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Email sudah digunakan oleh user lain.']);
        }

        // status_jabatan/nip are NOT NULL in the schema but legacy never collected them
        // here — insert empty strings to keep parity with legacy's (non-strict-mode) insert.
        User::create([
            'nama' => $nama,
            'status_jabatan' => '',
            'nip' => '',
            'email' => $email,
            'password' => md5($email), // legacy compat placeholder; login is Google-OAuth only.
            'role' => $role,
            'bidang' => $bidang,
            'is_aktif' => $isAktif,
        ]);

        return response()->json(['status' => 'success', 'message' => 'User berhasil ditambahkan.']);
    }

    public function addGmail(Request $request)
    {
        $this->abortIfNotAdmin();

        $userId = (int) $request->input('username_id');
        $email = trim((string) $request->input('email', ''));

        if ($userId === 0 || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 'error', 'message' => 'ID User atau format email tidak valid.']);
        }

        if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Email ini sudah terdaftar pada akun user lain.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }

        $user->update(['email' => $email]);

        return response()->json(['status' => 'success', 'message' => 'Alamat email berhasil diperbarui.']);
    }

    public function toggleStatus(Request $request)
    {
        $this->abortIfNotAdmin();

        $userId = (int) $request->input('id');
        if ($userId === 0) {
            return response()->json(['status' => 'error', 'message' => 'ID User tidak valid.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan atau status tidak berubah.']);
        }

        $user->update(['is_aktif' => ! $user->is_aktif]);

        return response()->json(['status' => 'success', 'message' => 'Status user berhasil diubah.']);
    }

    public function updateAccess(Request $request)
    {
        $this->abortIfNotAdmin();

        $userId = (int) $request->input('user_id');
        $pages = array_values((array) $request->input('pages', []));

        if ($userId === 0) {
            return response()->json(['status' => 'error', 'message' => 'ID User tidak valid.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui hak akses: user tidak ditemukan.']);
        }

        $user->update(['shared_pages' => $pages]);

        return response()->json(['status' => 'success', 'message' => 'Hak akses aplikasi berhasil diperbarui.']);
    }

    public function batchUpdateAccess(Request $request)
    {
        $this->abortIfNotAdmin();

        $userIds = array_values((array) $request->input('user_ids', []));
        $pages = array_values((array) $request->input('pages', []));

        if (empty($userIds)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada user yang dipilih.']);
        }

        $successCount = 0;
        foreach ($userIds as $id) {
            $affected = User::where('id', (int) $id)->update(['shared_pages' => json_encode($pages)]);
            if ($affected > 0) {
                $successCount++;
            }
        }

        return response()->json(['status' => 'success', 'message' => "Berhasil memperbarui hak akses untuk {$successCount} user."]);
    }
}
