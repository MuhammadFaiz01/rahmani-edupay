<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TagihanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tagihan::with(['siswa.ortu']);
        
        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_tagihan', 'like', "%{$search}%")
                  ->orWhereHas('siswa', function($subQ) use ($search) {
                      $subQ->where('nama_siswa', 'like', "%{$search}%")
                           ->orWhere('kelas', 'like', "%{$search}%");
                  });
            });
        }
        
        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status_tagihan', $request->status);
        }
        
        // Month filter
        if ($request->has('bulan') && $request->bulan) {
            $query->whereMonth('jatuh_tempo', $request->bulan);
        }
        
        // Year filter
        if ($request->has('tahun') && $request->tahun) {
            $query->whereYear('jatuh_tempo', $request->tahun);
        }
        
        // Siswa filter
        if ($request->has('siswa') && $request->siswa) {
            $query->where('id_siswa', $request->siswa);
        }
        
        $tagihanList = $query->orderBy('jatuh_tempo', 'desc')->paginate(15);
        
        // Get data for filters
        $siswaList = Siswa::with('ortu')->orderBy('nama_siswa')->get();
        $statusList = ['pending', 'paid', 'overdue'];
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $tahunList = range(date('Y') - 2, date('Y') + 1);
        
        return view('admin.tagihan.index', compact(
            'tagihanList', 'siswaList', 'statusList', 'bulanList', 'tahunList'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $siswaList = Siswa::with('ortu')->orderBy('nama_siswa')->get();
        
        return view('admin.tagihan.create', compact('siswaList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_siswa' => 'required|exists:siswa,id_siswa',
            'nama_tagihan' => 'required|string|max:255',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'jatuh_tempo' => 'required|date|after_or_equal:today',
        ]);

        Tagihan::create([
            'id_siswa' => $request->id_siswa,
            'nama_tagihan' => $request->nama_tagihan,
            'jumlah_tagihan' => $request->jumlah_tagihan,
            'jatuh_tempo' => $request->jatuh_tempo,
            'status_tagihan' => 'pending',
        ]);

        return redirect()->route('admin.tagihan.index')
            ->with('success', 'Tagihan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tagihan $tagihan)
    {
        $tagihan->load(['siswa.ortu', 'pembayaran']);
        
        return view('admin.tagihan.show', compact('tagihan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tagihan $tagihan)
    {
        $siswaList = Siswa::with('ortu')->orderBy('nama_siswa')->get();
        
        return view('admin.tagihan.edit', compact('tagihan', 'siswaList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tagihan $tagihan)
    {
        $request->validate([
            'id_siswa' => 'required|exists:siswa,id_siswa',
            'nama_tagihan' => 'required|string|max:255',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'jatuh_tempo' => 'required|date',
            'status_tagihan' => 'required|in:pending,paid,overdue',
        ]);

        $tagihan->update([
            'id_siswa' => $request->id_siswa,
            'nama_tagihan' => $request->nama_tagihan,
            'jumlah_tagihan' => $request->jumlah_tagihan,
            'jatuh_tempo' => $request->jatuh_tempo,
            'status_tagihan' => $request->status_tagihan,
        ]);

        return redirect()->route('admin.tagihan.index')
            ->with('success', 'Tagihan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tagihan $tagihan)
    {
        // Check if tagihan has pembayaran
        if ($tagihan->pembayaran()->count() > 0) {
            return redirect()->route('admin.tagihan.index')
                ->with('error', 'Tidak dapat menghapus tagihan yang sudah memiliki pembayaran.');
        }

        $tagihan->delete();

        return redirect()->route('admin.tagihan.index')
            ->with('success', 'Tagihan berhasil dihapus.');
    }

    /**
     * Update overdue bills status
     */
    public function updateOverdue()
    {
        $overdueCount = Tagihan::where('jatuh_tempo', '<', Carbon::now()->toDateString())
            ->where('status_tagihan', 'pending')
            ->update(['status_tagihan' => 'overdue']);

        return redirect()->route('admin.tagihan.index')
            ->with('success', "Berhasil memperbarui {$overdueCount} tagihan yang sudah jatuh tempo.");
    }
}