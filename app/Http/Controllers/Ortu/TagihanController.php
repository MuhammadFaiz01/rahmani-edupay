<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Models\Siswa;
use App\Models\Pembayaran;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TagihanController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Tagihan::whereHas('siswa', function($q) use ($user) {
            $q->where('id_ortu', $user->id);
        })->with(['siswa', 'pembayaran']);
        
        // Filter by siswa
        if ($request->has('siswa') && $request->siswa) {
            $query->where('id_siswa', $request->siswa);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status_tagihan', $request->status);
        }
        
        // Filter by month
        if ($request->has('bulan') && $request->bulan) {
            $query->whereMonth('jatuh_tempo', $request->bulan);
        }
        
        $tagihanList = $query->orderBy('jatuh_tempo', 'desc')->paginate(15);
        
        // Get siswa list for filter
        $siswaList = Siswa::where('id_ortu', $user->id)->get();
        
        return view('ortu.tagihan.index', compact('tagihanList', 'siswaList'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Tagihan $tagihan)
    {
        // Check if this tagihan belongs to current user's siswa
        if ($tagihan->siswa->id_ortu !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        $tagihan->load(['siswa', 'pembayaran']);
        
        return view('ortu.tagihan.show', compact('tagihan'));
    }

    /**
     * Create payment for tagihan
     */
    public function createPayment(Request $request, Tagihan $tagihan)
    {
        // Check if this tagihan belongs to current user's siswa
        if ($tagihan->siswa->id_ortu !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        // Check if tagihan is already paid
        if ($tagihan->status_tagihan === 'paid') {
            return redirect()->route('ortu.tagihan.show', $tagihan)
                ->with('error', 'Tagihan sudah dibayar.');
        }
        
        try {
            $user = Auth::user();
            $result = $this->midtransService->createSnapToken(
                $tagihan,
                $user->id,
                $user->nama_lengkap,
                $user->email
            );
            
            return response()->json([
                'success' => true,
                'snap_token' => $result['snap_token'],
                'order_id' => $result['order_id']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Payment finish callback
     */
    public function paymentFinish(Request $request)
    {
        $orderId = $request->get('order_id');
        $statusCode = $request->get('status_code');
        $transactionStatus = $request->get('transaction_status');
        
        $pembayaran = Pembayaran::where('midtrans_order_id', $orderId)->first();
        
        if (!$pembayaran) {
            return redirect()->route('ortu.tagihan.index')
                ->with('error', 'Pembayaran tidak ditemukan.');
        }
        
        $tagihan = $pembayaran->tagihan;
        
        // Check if this tagihan belongs to current user's siswa
        if ($tagihan->siswa->id_ortu !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            return redirect()->route('ortu.tagihan.show', $tagihan)
                ->with('success', 'Pembayaran berhasil! Terima kasih.');
        } elseif ($transactionStatus === 'pending') {
            return redirect()->route('ortu.tagihan.show', $tagihan)
                ->with('info', 'Pembayaran sedang diproses. Silakan tunggu konfirmasi.');
        } else {
            return redirect()->route('ortu.tagihan.show', $tagihan)
                ->with('error', 'Pembayaran gagal atau dibatalkan.');
        }
    }

    /**
     * Upload manual payment proof
     */
    public function uploadProof(Request $request, Tagihan $tagihan)
    {
        // Check if this tagihan belongs to current user's siswa
        if ($tagihan->siswa->id_ortu !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'bukti_pembayaran' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'jumlah_dibayar' => 'required|numeric|min:1|max:' . $tagihan->jumlah_tagihan,
        ]);
        
        // Store file
        $path = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');
        
        // Create pembayaran record
        Pembayaran::create([
            'id_tagihan' => $tagihan->id_tagihan,
            'jml_dibayar' => $request->jumlah_dibayar,
            'metode_pembayaran' => 'manual_transfer',
            'status_pembayaran' => 'pending',
            'bukti_pembayaran' => $path,
        ]);
        
        return redirect()->route('ortu.tagihan.show', $tagihan)
            ->with('success', 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.');
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(Pembayaran $pembayaran)
    {
        // Check if this pembayaran belongs to current user's siswa
        if ($pembayaran->tagihan->siswa->id_ortu !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        // Check if payment is successful
        if ($pembayaran->status_pembayaran !== 'success') {
            return redirect()->back()
                ->with('error', 'Hanya pembayaran yang berhasil yang dapat dicetak.');
        }
        
        $pembayaran->load(['tagihan.siswa']);
        
        return view('ortu.tagihan.receipt', compact('pembayaran'));
    }

    /**
     * Payment history
     */
    public function paymentHistory(Request $request)
    {
        $user = Auth::user();
        
        $query = Pembayaran::whereHas('tagihan.siswa', function($q) use ($user) {
            $q->where('id_ortu', $user->id);
        })->with(['tagihan.siswa']);
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status_pembayaran', $request->status);
        }
        
        // Filter by siswa
        if ($request->has('siswa') && $request->siswa) {
            $query->whereHas('tagihan', function($q) use ($request) {
                $q->where('id_siswa', $request->siswa);
            });
        }
        
        $pembayaranList = $query->orderBy('tgl_dibuat', 'desc')->paginate(15);
        
        // Get siswa list for filter
        $siswaList = Siswa::where('id_ortu', $user->id)->get();
        
        return view('ortu.tagihan.payment-history', compact('pembayaranList', 'siswaList'));
    }
}