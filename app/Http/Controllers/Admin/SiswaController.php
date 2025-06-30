<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Siswa::with('ortu');
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%")
                  ->orWhereHas('ortu', function($subQ) use ($search) {
                      $subQ->where('nama_lengkap', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('kelas') && $request->kelas) {
            $query->where('kelas', $request->kelas);
        }
        
        $siswaList = $query->orderBy('nama_siswa')->paginate(15);
        
        // Get unique kelas for filter
        $kelasList = Siswa::distinct()->pluck('kelas')->sort();
        
        return view('admin.siswa.index', compact('siswaList', 'kelasList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ortuList = User::where('role', 'ortu')->orderBy('nama_lengkap')->get();
        
        return view('admin.siswa.create', compact('ortuList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_ortu' => 'required|exists:users,id',
            'nama_siswa' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
        ]);

        Siswa::create([
            'id_ortu' => $request->id_ortu,
            'nama_siswa' => $request->nama_siswa,
            'kelas' => $request->kelas,
        ]);

        return redirect()->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Siswa $siswa)
    {
        $siswa->load(['ortu', 'tagihan.pembayaran']);
        
        return view('admin.siswa.show', compact('siswa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa)
    {
        $ortuList = User::where('role', 'ortu')->orderBy('nama_lengkap')->get();
        
        return view('admin.siswa.edit', compact('siswa', 'ortuList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Siswa $siswa)
    {
        $request->validate([
            'id_ortu' => 'required|exists:users,id',
            'nama_siswa' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
        ]);

        $siswa->update([
            'id_ortu' => $request->id_ortu,
            'nama_siswa' => $request->nama_siswa,
            'kelas' => $request->kelas,
        ]);

        return redirect()->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa)
    {
        // Check if siswa has tagihan
        if ($siswa->tagihan()->count() > 0) {
            return redirect()->route('admin.siswa.index')
                ->with('error', 'Tidak dapat menghapus siswa yang masih memiliki tagihan.');
        }

        $siswa->delete();

        return redirect()->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil dihapus.');
    }
}