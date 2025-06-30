<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get all siswa for this parent
        $siswaList = Siswa::where('id_ortu', $user->id)->with(['tagihan'])->get();
        
        // Get statistics
        $totalSiswa = $siswaList->count();
        $totalTagihanPending = 0;
        $totalTagihanOverdue = 0;
        $totalTagihanPaid = 0;
        $totalJumlahTagihan = 0;
        
        foreach ($siswaList as $siswa) {
            foreach ($siswa->tagihan as $tagihan) {
                if ($tagihan->status_tagihan === 'pending') {
                    $totalTagihanPending++;
                    $totalJumlahTagihan += $tagihan->jumlah_tagihan;
                } elseif ($tagihan->status_tagihan === 'overdue') {
                    $totalTagihanOverdue++;
                    $totalJumlahTagihan += $tagihan->jumlah_tagihan;
                } elseif ($tagihan->status_tagihan === 'paid') {
                    $totalTagihanPaid++;
                }
            }
        }
        
        // Get recent payments
        $recentPayments = Pembayaran::whereHas('tagihan.siswa', function($query) use ($user) {
                $query->where('id_ortu', $user->id);
            })
            ->with(['tagihan.siswa'])
            ->where('status_pembayaran', 'success')
            ->orderBy('tgl_pembayaran', 'desc')
            ->limit(5)
            ->get();
        
        // Get upcoming due dates
        $upcomingDueDates = Tagihan::whereHas('siswa', function($query) use ($user) {
                $query->where('id_ortu', $user->id);
            })
            ->with('siswa')
            ->where('status_tagihan', 'pending')
            ->where('jatuh_tempo', '>=', Carbon::now()->toDateString())
            ->where('jatuh_tempo', '<=', Carbon::now()->addDays(7)->toDateString())
            ->orderBy('jatuh_tempo', 'asc')
            ->limit(5)
            ->get();
        
        // Get overdue bills
        $overdueBills = Tagihan::whereHas('siswa', function($query) use ($user) {
                $query->where('id_ortu', $user->id);
            })
            ->with('siswa')
            ->where('jatuh_tempo', '<', Carbon::now()->toDateString())
            ->where('status_tagihan', '!=', 'paid')
            ->orderBy('jatuh_tempo', 'asc')
            ->limit(5)
            ->get();
        
        // Get recent announcements
        $recentAnnouncements = Pengumuman::forOrtu()
            ->orderBy('tgl_dibuat', 'desc')
            ->limit(5)
            ->get();
        
        return view('ortu.dashboard', compact(
            'siswaList',
            'totalSiswa',
            'totalTagihanPending',
            'totalTagihanOverdue', 
            'totalTagihanPaid',
            'totalJumlahTagihan',
            'recentPayments',
            'upcomingDueDates',
            'overdueBills',
            'recentAnnouncements'
        ));
    }
}