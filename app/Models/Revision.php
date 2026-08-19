<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une entree du journal des modifications.
 *
 * Ecrite automatiquement par le trait RecordsRevisions : rien n'est a
 * declencher depuis les controleurs, aucune modification ne peut donc
 * echapper au journal.
 */
class Revision extends Model
{
    /** Le journal n'est jamais modifie : seul created_at a un sens. */
    public $timestamps = false;

    protected $fillable = ['revisable_type', 'revisable_id', 'user_id', 'action', 'changes'];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    public function revisable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Libelle lisible de l'action, pour l'affichage. */
    public function libelleAction(): string
    {
        return match ($this->action) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            default   => $this->action,
        };
    }
}
