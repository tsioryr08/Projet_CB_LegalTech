<?php

namespace App\Controllers;

class ProprietaireController extends BaseController
{
    public function tableauDeBord(): string
    {
        return view('proprietaire/tableau_de_bord', [
            'nom' => session('prenoms') ?? session('nom'),
        ]);
    }
}