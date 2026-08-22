<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $regles = [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'full_phone' => ['nullable', 'string', 'max:20'],
        ];

        // Le nom sert d'identifiant dans le Back Office Admin (recherche,
        // repartition des utilisateurs) : il est defini par l'Admin, un
        // Student ne doit pas pouvoir le changer. Absent des regles, il est
        // absent de validated() donc jamais applique, meme si la requete est
        // forgee avec un champ "name".
        if (! $this->user()->hasRole('student')) {
            $regles['name'] = ['required', 'string', 'max:255'];
        }

        return $regles;
    }
}
