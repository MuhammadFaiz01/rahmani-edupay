<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pembayaran::with(['tagihan.siswa.ortu']);
        
        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('midtrans_order_id', 'like', "%{$search}%")
                  ->orWhere('midtrans_trx_id', 'like', "%{$search}%")
                  ->orWhereHas('tagihan.siswa', function($subQ) use ($search) {
                      $subQ->where('nama_siswa', 'like', "%{$search}%");
                  });
            });
        }
        
        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status_pembayaran', $request->status);
        }
        
        // Date range filter
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $query->whereDate('tgl_pembayaran', '>=', $request->tanggal_mulai);
        }
        
        if ($request->has('tanggal_selesai') && $request->tanggal_selesai) {
            $query->whereDate('tgl_pembayaran', '<=', $request->tanggal_selesai);
        }
        
        $pembayaranList = $query->orderBy('tgl_dibuat', 'desc')->paginate(15);
        
        // Statistics
        $totalPembayaran = $query->count();
        $totalSuccess = $query->where('status_pembayaran', 'success')->sum('jml_dibayar');
        $totalPending = $query->where('status_pembayaran', 'pending')->count();
        
        return view('admin.pembayaran.index', compact(
            'pembayaranList', 'totalPembayaran', 'totalSuccess', 'totalPending'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(Pembayaran $pembayaran)
    {
        $pembayaran->load(['tagihan.siswa.ortu']);
        
        return view('admin.pembayaran.show', compact('pembayaran'));
    }

    /**
     * Verify manual payment
     */
    public function verify(Request $request, Pembayaran $pembayaran)
    {
        $request->validate([
            'status' => 'required|in:success,failed',
            'catatan' => 'nullable|string|max:500',
        ]);

        $pembayaran->status_pembayaran = $request->status;
        
        if ($request->status === 'success') {
            $pembayaran->tgl_pembayaran = now();
            
            // Update tagihan status
            $tagihan = $pembayaran->tagihan;
            $totalPaid = $tagihan->pembayaranSuccess()->sum('jml_dibayar') + $pembayaran->jml_dibayar;
            
            if ($totalPaid >= $tagihan->jumlah_tagihan) {
                $tagihan->status_tagihan = 'paid';
                $tagihan->save();
            }
        }
        
        $pembayaran->save();

        return redirect()->route('admin.pembayaran.show', $pembayaran)
            ->with('success', 'Status pembayaran berhasil diverifikasi.');
    }

    /**
     * Upload manual payment proof
     */
    public function uploadProof(Request $request, Pembayaran $pembayaran)
    {
        $request->validate([
            'bukti_pembayaran' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Delete old file if exists
        if ($pembayaran->bukti_pembayaran && Storage::exists($pembayaran->bukti_pembayaran)) {
            Storage::delete($pembayaran->bukti_pembayaran);
        }

        // Store new file
        $path = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');
        
        $pembayaran->bukti_pembayaran = $path;
        $pembayaran->save();

        return redirect()->route('admin.pembayaran.show', $pembayaran)
            ->with('success', 'Bukti pembayaran berhasil diupload.');
    }

    /**
     * Generate payment report
     */
    public function report(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'format' => 'required|in:pdf,excel',
        ]);

        $pembayaranList = Pembayaran::with(['tagihan.siswa.ortu'])
            ->where('status_pembayaran', 'success')
            ->whereBetween('tgl_pembayaran', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->orderBy('tgl_pembayaran', 'desc')
            ->get();

        $totalAmount = $pembayaranList->sum('jml_dibayar');
        $totalCount = $pembayaranList->count();

        if ($request->format === 'pdf') {
            return $this->generatePdfReport($pembayaranList, $request->tanggal_mulai, $request->tanggal_selesai, $totalAmount, $totalCount);
        } else {
            return $this->generateExcelReport($pembayaranList, $request->tanggal_mulai, $request->tanggal_selesai, $totalAmount, $totalCount);
        }
    }

    /**
     * Generate PDF report
     */
    private function generatePdfReport($pembayaranList, $tanggalMulai, $tanggalSelesai, $totalAmount, $totalCount)
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.pembayaran.report-pdf', compact(
            'pembayaranList', 'tanggalMulai', 'tanggalSelesai', 'totalAmount', 'totalCount'
        ));
        
        return $pdf->download('laporan-pembayaran-' . $tanggalMulai . '-' . $tanggalSelesai . '.pdf');
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport($pembayaranList, $tanggalMulai, $tanggalSelesai, $totalAmount, $totalCount)
    {
        return Excel::download(
            new PembayaranExport($pembayaranList, $tanggalMulai, $tanggalSelesai, $totalAmount, $totalCount),
            'laporan-pembayaran-' . $tanggalMulai . '-' . $tanggalSelesai . '.xlsx'
        );
    }

    /**
     * Daily report
     */
    public function dailyReport(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        
        $pembayaranList = Pembayaran::with(['tagihan.siswa.ortu'])
            ->where('status_pembayaran', 'success')
            ->whereDate('tgl_pembayaran', $tanggal)
            ->orderBy('tgl_pembayaran', 'desc')
            ->get();

        $totalAmount = $pembayaranList->sum('jml_dibayar');
        $totalCount = $pembayaranList->count();

        return view('admin.pembayaran.daily-report', compact(
            'pembayaranList', 'tanggal', 'totalAmount', 'totalCount'
        ));
    }

    /**
     * Monthly report
     */
    public function monthlyReport(Request $request)
    {
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        
        $pembayaranList = Pembayaran::with(['tagihan.siswa.ortu'])
            ->where('status_pembayaran', 'success')
            ->whereMonth('tgl_pembayaran', $bulan)
            ->whereYear('tgl_pembayaran', $tahun)
            ->orderBy('tgl_pembayaran', 'desc')
            ->get();

        $totalAmount = $pembayaranList->sum('jml_dibayar');
        $totalCount = $pembayaranList->count();

        return view('admin.pembayaran.monthly-report', compact(
            'pembayaranList', 'bulan', 'tahun', 'totalAmount', 'totalCount'
        ));
    }
}