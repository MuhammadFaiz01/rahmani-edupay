<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OrtuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'ortu')->with('siswa');
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $ortuList = $query->orderBy('nama_lengkap')->paginate(15);
        
        return view('admin.ortu.index', compact('ortuList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ortu.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'ortu',
        ]);

        return redirect()->route('admin.ortu.index')
            ->with('success', 'Data orang tua berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $ortu)
    {
        $ortu->load(['siswa.tagihan.pembayaran']);
        
        return view('admin.ortu.show', compact('ortu'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $ortu)
    {
        return view('admin.ortu.edit', compact('ortu'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $ortu)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($ortu->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $ortu->update($data);

        return redirect()->route('admin.ortu.index')
            ->with('success', 'Data orang tua berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $ortu)
    {
        // Check if ortu has siswa
        if ($ortu->siswa()->count() > 0) {
            return redirect()->route('admin.ortu.index')
                ->with('error', 'Tidak dapat menghapus orang tua yang masih memiliki siswa.');
        }

        $ortu->delete();

        return redirect()->route('admin.ortu.index')
            ->with('success', 'Data orang tua berhasil dihapus.');
    }
}