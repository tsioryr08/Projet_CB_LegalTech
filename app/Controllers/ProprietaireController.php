<?php

namespace App\Controllers;

use App\Models\ContratModel;

class ProprietaireController extends BaseController
{
    public function tableauDeBord(): string
    {
        $contratModel = new ContratModel();

        return view('proprietaire/tableau_de_bord', [
            'nom' => session('prenoms') ?? session('nom'),
            'contratsActifs' => $contratModel->where('id_proprietaire', (int) session('id_utilisateur'))->whereIn('statut', ['signe_bailleur', 'actif'])->countAllResults(),
            'contratsAAttention' => $contratModel->where('id_proprietaire', (int) session('id_utilisateur'))->where('statut', 'genere')->countAllResults(),
        ]);
    }
}