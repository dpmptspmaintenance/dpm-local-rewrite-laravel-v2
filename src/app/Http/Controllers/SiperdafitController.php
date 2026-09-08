<?php

namespace App\Http\Controllers;

use App\Models\ItRequest;
use App\Models\ItRequestFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SiperdafitController extends Controller
{
    public function index()
    {
        return view('siperdafit.index');
    }

    public function list(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $type = trim((string) $request->query('type', ''));

        $requests = ItRequest::query()
            ->with(['user:id,nama', 'approver:id,nama', 'accepter:id,nama', 'rejecter:id,nama', 'files'])
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = "%{$keyword}%";
                $q->where(function ($q2) use ($like) {
                    $q2->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('user', fn ($q3) => $q3->where('nama', 'like', $like));
                });
            })
            ->when($type !== '', fn ($q) => $q->where('request_type', $type))
            ->orderByRaw("(CASE WHEN status = 'approved' THEN priority ELSE 0 END) DESC")
            ->orderByDesc('created_at')
            ->get();

        $data = $requests->map(function (ItRequest $item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'title' => $item->title,
                'description' => $item->description,
                'request_type' => $item->request_type,
                'status' => $item->status,
                'priority' => $item->priority,
                'rejection_reason' => $item->rejection_reason,
                'pending_reason' => $item->pending_reason,
                'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
                'approved_at' => optional($item->approved_at)->format('Y-m-d H:i:s'),
                'accepted_at' => optional($item->accepted_at)->format('Y-m-d H:i:s'),
                'finish_time' => optional($item->finish_time)->format('Y-m-d H:i:s'),
                'username_pemohon' => $item->user->nama ?? 'User',
                'username_approver' => $item->approver->nama ?? 'IT Admin',
                'username_penerima' => $item->accepter->nama ?? 'IT Admin',
                'username_penolak' => $item->rejecter->nama ?? 'IT Admin',
                'files' => $item->files->map(fn (ItRequestFile $f) => [
                    'name' => $f->file_name,
                    'path' => $f->file_path,
                ])->values(),
            ];
        });

        return response()->json([
            'data' => $data,
            'user_session' => ['id' => Auth::id(), 'role' => Auth::user()->role ?? 0],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'request_type' => ['required', 'in:penambahan_fitur,permintaan_data'],
            'files.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $itRequest = ItRequest::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'request_type' => $validated['request_type'],
            'status' => 'belum_dikerjakan',
            'created_at' => now(),
        ]);

        $this->storeUploadedFiles($request, $itRequest->id);

        return response()->json(['status' => 'success']);
    }

    public function detail(ItRequest $itRequest)
    {
        $itRequest->load('files');

        $data = $itRequest->toArray();
        $data['files'] = $itRequest->files->map(fn (ItRequestFile $f) => [
            'id' => $f->id,
            'request_id' => $f->request_id,
            'file_name' => $f->file_name,
            'file_path' => $f->file_path,
        ])->values();

        return response()->json($data);
    }

    public function update(Request $request, ItRequest $itRequest)
    {
        if ((int) $itRequest->user_id !== (int) Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'request_type' => ['required', 'in:penambahan_fitur,permintaan_data'],
            'files.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $itRequest->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'request_type' => $validated['request_type'],
        ]);

        $this->storeUploadedFiles($request, $itRequest->id);

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request, ItRequest $itRequest)
    {
        if ((int) $itRequest->user_id !== (int) Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        foreach ($itRequest->files as $file) {
            $relative = preg_replace('#^/storage/#', '', $file->file_path);
            Storage::disk('public')->delete($relative);
        }

        $itRequest->delete();

        return response()->json(['status' => 'success']);
    }

    public function updateStatus(Request $request)
    {
        if ((int) (Auth::user()->role ?? 0) !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Akses Ditolak']);
        }

        $itRequest = ItRequest::findOrFail((int) $request->input('id'));
        $status = (string) $request->input('status');
        $reason = $request->input('reason');
        $priority = (int) $request->input('priority', 0);
        $currentUserId = Auth::id();

        $data = match ($status) {
            'rejected' => [
                'status' => $status,
                'rejected_by' => $currentUserId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'pending_reason' => null,
                'priority' => 0,
            ],
            'approved' => [
                'status' => $status,
                'priority' => $priority,
                'approved_by' => $currentUserId,
                'approved_at' => now(),
                'pending_reason' => null,
            ],
            'pengerjaan' => [
                'status' => $status,
                'accepted_by' => $currentUserId,
                'accepted_at' => now(),
                'pending_reason' => null,
            ],
            'selesai' => [
                'status' => $status,
                'accepted_by' => $currentUserId,
                'finish_time' => now(),
                'pending_reason' => null,
            ],
            'pending' => [
                'status' => $status,
                'pending_reason' => $reason,
            ],
            default => [
                'status' => $status,
                'priority' => 0,
                'pending_reason' => null,
                'rejection_reason' => null,
                'approved_by' => null,
                'approved_at' => null,
                'accepted_by' => null,
                'accepted_at' => null,
                'finish_time' => null,
                'rejected_by' => null,
                'rejected_at' => null,
            ],
        };

        $itRequest->update($data);

        return response()->json(['status' => 'success']);
    }

    public function updatePriority(Request $request)
    {
        if ((int) (Auth::user()->role ?? 0) !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Akses Ditolak']);
        }

        $itRequest = ItRequest::findOrFail((int) $request->input('id'));
        $itRequest->update(['priority' => (int) $request->input('priority', 0)]);

        return response()->json(['status' => 'success']);
    }

    private function storeUploadedFiles(Request $request, int $requestId): void
    {
        if (! $request->hasFile('files')) {
            return;
        }

        foreach ($request->file('files') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $storedName = time().'_'.$originalName;
            $storedPath = $file->storeAs('siperdafit', $storedName, 'public');

            ItRequestFile::create([
                'request_id' => $requestId,
                'file_name' => $originalName,
                'file_path' => '/storage/'.$storedPath,
                'uploaded_at' => now(),
            ]);
        }
    }
}
