<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tagihan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tagihan';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_tagihan';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'tgl_dibuat';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'tgl_diperbarui';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_siswa',
        'nama_tagihan',
        'jumlah_tagihan',
        'jatuh_tempo',
        'status_tagihan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_tagihan' => 'decimal:2',
            'jatuh_tempo' => 'date',
            'tgl_dibuat' => 'datetime',
            'tgl_diperbarui' => 'datetime',
        ];
    }

    /**
     * Get the siswa that owns the tagihan.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }

    /**
     * Get the pembayaran for the tagihan.
     */
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_tagihan');
    }

    /**
     * Get successful pembayaran for the tagihan.
     */
    public function pembayaranSuccess()
    {
        return $this->hasMany(Pembayaran::class, 'id_tagihan')->where('status_pembayaran', 'success');
    }

    /**
     * Check if tagihan is overdue.
     */
    public function isOverdue()
    {
        return $this->jatuh_tempo < Carbon::now()->toDateString() && $this->status_tagihan !== 'paid';
    }

    /**
     * Check if tagihan is paid.
     */
    public function isPaid()
    {
        return $this->status_tagihan === 'paid';
    }

    /**
     * Get total amount paid for this tagihan.
     */
    public function getTotalPaidAttribute()
    {
        return $this->pembayaranSuccess()->sum('jml_dibayar');
    }

    /**
     * Get remaining amount to be paid.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->jumlah_tagihan - $this->total_paid;
    }

    /**
     * Scope to get overdue tagihan.
     */
    public function scopeOverdue($query)
    {
        return $query->where('jatuh_tempo', '<', Carbon::now()->toDateString())
                    ->where('status_tagihan', '!=', 'paid');
    }

    /**
     * Scope to get pending tagihan.
     */
    public function scopePending($query)
    {
        return $query->where('status_tagihan', 'pending');
    }

    /**
     * Scope to get paid tagihan.
     */
    public function scopePaid($query)
    {
        return $query->where('status_tagihan', 'paid');
    }
}