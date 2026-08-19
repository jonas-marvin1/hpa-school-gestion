<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un palier de l'echelle de niveau d'anglais (A1 -> C2+).
 *
 * L'ordre de progression est porte par `position`, jamais par l'id :
 * un palier pourrait etre insere entre deux existants sans casser le tri.
 */
class EnglishLevel extends Model
{
    protected $fillable = ['code', 'label', 'position'];

    public function students()
    {
        return $this->hasMany(User::class, 'english_level_id');
    }

    /** L'echelle complete, dans l'ordre. */
    public static function echelle()
    {
        return static::orderBy('position')->get();
    }

    /** Palier suivant, ou null si le sommet est atteint. */
    public function suivant(): ?self
    {
        return static::where('position', '>', $this->position)
            ->orderBy('position')
            ->first();
    }

    /** Palier precedent, ou null si l'on est au premier. */
    public function precedent(): ?self
    {
        return static::where('position', '<', $this->position)
            ->orderByDesc('position')
            ->first();
    }

    /** Avancement sur l'echelle, en pourcentage. */
    public function progression(): int
    {
        $total = static::max('position') ?: 1;

        return (int) round($this->position / $total * 100);
    }
}
