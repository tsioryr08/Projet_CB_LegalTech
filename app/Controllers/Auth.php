<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;

class Auth extends BaseController
{
    protected UtilisateurModel $utilisateurModel;

    public function __construct()
    {
        $this->utilisateurModel = new UtilisateurModel();
    }

    /**
     * $role attendu : 'client' ou 'proprietaire'
     * Sécurise la valeur pour ne jamais permettre 'admin' via l'URL publique.
     */
    private function roleValide(string $role): string
    {
        return in_array($role, ['client', 'proprietaire'], true) ? $role : 'client';
    }

    // -----------------------------------------------------------------
    // CONNEXION
    // -----------------------------------------------------------------

    public function formulaireConnexion(string $role)
    {
        $role = $this->roleValide($role);

        return view('auth/login', ['role' => $role]);
    }

    public function connexion(string $role)
    {
        $role = $this->roleValide($role);

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('mot_de_passe');

        $utilisateur = $this->utilisateurModel->trouverParEmail($email, $role);

        if (! $utilisateur || ! password_verify($password, $utilisateur['mot_de_passe_hash'])) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', 'E-mail ou mot de passe incorrect.');
        }

        if ($utilisateur['statut_compte'] !== 'actif') {
            return redirect()->back()->with('erreur', 'Ce compte est suspendu.');
        }

        session()->set([
            'id_utilisateur' => $utilisateur['id_utilisateur'],
            'nom'            => $utilisateur['nom'],
            'prenoms'        => $utilisateur['prenoms'],
            'role'           => $utilisateur['role'],
            'connecte'       => true,
        ]);

        return match ($utilisateur['role']) {
            'client'       => redirect()->to('/client/catalogue'),
            'proprietaire' => redirect()->to('/proprietaire/tableau-de-bord'),
            default        => redirect()->to('/'),
        };
    }

    // -----------------------------------------------------------------
    // INSCRIPTION
    // -----------------------------------------------------------------

    public function formulaireInscription(string $role)
    {
        $role = $this->roleValide($role);

        return view('auth/register', ['role' => $role]);
    }

    public function inscription(string $role)
    {
        $role = $this->roleValide($role);

        $regles = [
            'nom'           => 'required|min_length[2]|max_length[100]',
            'prenoms'       => 'permit_empty|max_length[150]',
            'email'         => 'required|valid_email|is_unique[utilisateurs.email]',
            'mot_de_passe'  => 'required|min_length[6]',
            'telephone'     => 'permit_empty|max_length[30]',
        ];

        if (! $this->validate($regles)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $donnees = [
            'role'              => $role,
            'nom'               => $this->request->getPost('nom'),
            'prenoms'           => $this->request->getPost('prenoms'),
            'email'             => $this->request->getPost('email'),
            'mot_de_passe_hash' => password_hash($this->request->getPost('mot_de_passe'), PASSWORD_DEFAULT),
            'telephone'         => $this->request->getPost('telephone'),
            'statut_compte'     => 'actif',
        ];

        $this->utilisateurModel->insert($donnees);

        return redirect()->to('/auth/connexion/' . $role)
            ->with('succes', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
    }

    // -----------------------------------------------------------------
    // DECONNEXION
    // -----------------------------------------------------------------

    public function deconnexion()
    {
        session()->destroy();

        return redirect()->to('/')->with('succes', 'Vous avez été déconnecté.');
    }
}