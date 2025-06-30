<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pengumuman::forOrtu()->with('user');
        
        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('isi_pengumuman', 'like', "%{$search}%");
            });
        }
        
        $pengumumanList = $query->orderBy('tgl_dibuat', 'desc')->paginate(10);
        
        return view('ortu.pengumuman.index', compact('pengumumanList'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Pengumuman $pengumuman)
    {
        // Check if announcement is for ortu or semua
        if (!in_array($pengumuman->ditujukan_ke, ['ortu', 'semua'])) {
            abort(403, 'Unauthorized access');
        }
        
        $pengumuman->load('user');
        
        return view('ortu.pengumuman.show', compact('pengumuman'));
    }
}