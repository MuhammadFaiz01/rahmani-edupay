<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics for dashboard
        $totalOrtu = User::where('role', 'ortu')->count();
        $totalSiswa = Siswa::count();
        $totalTagihan = Tagihan::count();
        $totalTagihanPending = Tagihan::where('status_tagihan', 'pending')->count();
        $totalTagihanOverdue = Tagihan::where('status_tagihan', 'overdue')->count();
        $totalTagihanPaid = Tagihan::where('status_tagihan', 'paid')->count();
        
        // Payment statistics
        $totalPembayaranSuccess = Pembayaran::where('status_pembayaran', 'success')->count();
        $totalPendapatanBulanIni = Pembayaran::where('status_pembayaran', 'success')
            ->whereMonth('tgl_pembayaran', Carbon::now()->month)
            ->whereYear('tgl_pembayaran', Carbon::now()->year)
            ->sum('jml_dibayar');
        
        $totalPendapatanHariIni = Pembayaran::where('status_pembayaran', 'success')
            ->whereDate('tgl_pembayaran', Carbon::today())
            ->sum('jml_dibayar');

        // Recent payments
        $recentPayments = Pembayaran::with(['tagihan.siswa'])
            ->where('status_pembayaran', 'success')
            ->orderBy('tgl_pembayaran', 'desc')
            ->limit(10)
            ->get();

        // Overdue bills
        $overdueTagihan = Tagihan::with(['siswa'])
            ->where('jatuh_tempo', '<', Carbon::now()->toDateString())
            ->where('status_tagihan', '!=', 'paid')
            ->orderBy('jatuh_tempo', 'asc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrtu',
            'totalSiswa', 
            'totalTagihan',
            'totalTagihanPending',
            'totalTagihanOverdue',
            'totalTagihanPaid',
            'totalPembayaranSuccess',
            'totalPendapatanBulanIni',
            'totalPendapatanHariIni',
            'recentPayments',
            'overdueTagihan'
        ));
    }
}