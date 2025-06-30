<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pembayaran';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_pembayaran';

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
        'id_tagihan',
        'midtrans_order_id',
        'midtrans_trx_id',
        'jml_dibayar',
        'metode_pembayaran',
        'status_pembayaran',
        'bukti_pembayaran',
        'tgl_pembayaran',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jml_dibayar' => 'decimal:2',
            'tgl_pembayaran' => 'datetime',
            'tgl_dibuat' => 'datetime',
            'tgl_diperbarui' => 'datetime',
        ];
    }

    /**
     * Get the tagihan that owns the pembayaran.
     */
    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan');
    }

    /**
     * Check if pembayaran is successful.
     */
    public function isSuccess()
    {
        return $this->status_pembayaran === 'success';
    }

    /**
     * Check if pembayaran is pending.
     */
    public function isPending()
    {
        return $this->status_pembayaran === 'pending';
    }

    /**
     * Check if pembayaran is failed.
     */
    public function isFailed()
    {
        return $this->status_pembayaran === 'failed';
    }

    /**
     * Scope to get successful pembayaran.
     */
    public function scopeSuccess($query)
    {
        return $query->where('status_pembayaran', 'success');
    }

    /**
     * Scope to get pending pembayaran.
     */
    public function scopePending($query)
    {
        return $query->where('status_pembayaran', 'pending');
    }

    /**
     * Scope to get failed pembayaran.
     */
    public function scopeFailed($query)
    {
        return $query->where('status_pembayaran', 'failed');
    }

    /**
     * Get formatted payment amount.
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->jml_dibayar, 0, ',', '.');
    }
}