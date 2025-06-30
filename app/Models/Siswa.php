<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'siswa';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_siswa';

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
        'id_ortu',
        'nama_siswa',
        'kelas',
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
     * Get the parent (user) that owns the siswa.
     */
    public function ortu()
    {
        return $this->belongsTo(User::class, 'id_ortu');
    }

    /**
     * Get the tagihan for the siswa.
     */
    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'id_siswa');
    }

    /**
     * Get pending tagihan for the siswa.
     */
    public function tagihanPending()
    {
        return $this->hasMany(Tagihan::class, 'id_siswa')->where('status_tagihan', 'pending');
    }

    /**
     * Get overdue tagihan for the siswa.
     */
    public function tagihanOverdue()
    {
        return $this->hasMany(Tagihan::class, 'id_siswa')->where('status_tagihan', 'overdue');
    }
}