<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pengumuman';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_pengumuman';

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
        'id_user',
        'judul',
        'isi_pengumuman',
        'ditujukan_ke',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_dibuat' => 'datetime',
            'tgl_diperbarui' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the pengumuman.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Scope to get pengumuman for parents.
     */
    public function scopeForOrtu($query)
    {
        return $query->whereIn('ditujukan_ke', ['ortu', 'semua']);
    }

    /**
     * Scope to get pengumuman for students.
     */
    public function scopeForSiswa($query)
    {
        return $query->whereIn('ditujukan_ke', ['siswa', 'semua']);
    }

    /**
     * Scope to get pengumuman for all.
     */
    public function scopeForSemua($query)
    {
        return $query->where('ditujukan_ke', 'semua');
    }

    /**
     * Get formatted created date.
     */
    public function getFormattedDateAttribute()
    {
        return $this->tgl_dibuat->format('d M Y H:i');
    }

    /**
     * Get excerpt of announcement content.
     */
    public function getExcerptAttribute($length = 150)
    {
        return strlen($this->isi_pengumuman) > $length 
            ? substr($this->isi_pengumuman, 0, $length) . '...' 
            : $this->isi_pengumuman;
    }
}