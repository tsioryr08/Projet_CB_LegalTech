<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bloque l'accès si l'utilisateur n'est pas connecté avec le bon rôle.
 * Usage dans les routes : 'filter' => 'auth:client'  ou  'filter' => 'auth:proprietaire'
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('connecte')) {
            return redirect()->to('/')->with('erreur', 'Veuillez vous connecter.');
        }

        // Si un rôle précis est exigé par la route (ex. 'auth:client')
        if (! empty($arguments) && session('role') !== $arguments[0]) {
            return redirect()->to('/')->with('erreur', 'Accès non autorisé pour ce rôle.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // rien à faire après
    }
}