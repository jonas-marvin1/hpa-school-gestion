<?php

namespace App\Models;

use App\Models\Concerns\RecordsRevisions;
use Illuminate\Database\Eloquent\Model;

/**
 * Plan de paiement d'une formation.
 *
 * Le solde restant n'est jamais stocke : il se recalcule a partir du total,
 * de l'avance et des echeances reglees. Une valeur stockee finirait par
 * diverger des mouvements reels.
 */
class PaymentPlan extends Model
{
    use RecordsRevisions;

    protected $fillable = [
        'student_id', 'program_id', 'total_amount',
        'advance_amount', 'currency', 'notes',
    ];

    protected $casts = [
        'total_amount'   => 'decimal:2',
        'advance_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** Les echeances du plan, de la plus proche a la plus lointaine. */
    public function echeances()
    {
        return $this->hasMany(StudentPayment::class)->orderBy('due_date');
    }

    /** Total deja encaisse : l'avance plus les echeances reglees. */
    public function montantRegle(): float
    {
        return (float) $this->advance_amount
             + (float) $this->echeances()->where('status', 'paid')->sum('amount');
    }

    /** Ce qu'il reste a payer sur l'ensemble de la formation. */
    public function soldeRestant(): float
    {
        return max(0, (float) $this->total_amount - $this->montantRegle());
    }

    /** Part de la formation deja reglee, en pourcentage. */
    public function progression(): int
    {
        $total = (float) $this->total_amount;

        return $total > 0 ? (int) round($this->montantRegle() / $total * 100) : 0;
    }

    /**
     * Prochaine echeance a honorer : la plus ancienne en attente.
     * Elle peut deja etre echue, auquel cas c'est bien elle qui est due.
     * Une echeance annulee n'est pas due : elle est exclue, sinon elle
     * apparaitrait comme la prochaine a payer.
     */
    public function prochaineEcheance(): ?StudentPayment
    {
        return $this->echeances()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Somme des echeances a venir, hors annulees : ce que le plan promet
     * encore de percevoir, en plus de ce qui est deja regle.
     */
    public function totalEcheancesAVenir(): float
    {
        return (float) $this->echeances()->where('status', 'pending')->sum('amount');
    }

    /**
     * Le plan est coherent si le cout total colle a son detail : montant
     * deja regle plus echeances a venir (hors annulees). Comme le cout
     * total est saisi a la main, il peut diverger de ce detail — parfois
     * legitimement (geste commercial, remise partielle) — d'ou une
     * tolerance d'une unite pour absorber les seuls arrondis de saisie
     * (point 5 du 19/09/2026, garde-fou non bloquant).
     */
    public function estCoherent(): bool
    {
        return abs($this->ecartCoherence()) < 1;
    }

    /**
     * Ecart entre le cout total affiche et son detail (positif si le total
     * annonce plus que ce que le detail justifie, negatif sinon).
     */
    public function ecartCoherence(): float
    {
        return (float) $this->total_amount - ($this->montantRegle() + $this->totalEcheancesAVenir());
    }
}
