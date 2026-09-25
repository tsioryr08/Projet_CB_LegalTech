<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalTech Bail Madagascar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
        }
        .carte-choix {
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
            border: none;
        }
        .carte-choix:hover {
            transform: translateY(-6px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,.25);
        }
        .icone-choix { font-size: 3rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center text-white mb-5">
        <h1 class="fw-bold">LegalTech — Contrat de Bail</h1>
        <p class="lead">Location d'habitat conforme à la législation malgache</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-md-5">
            <a href="<?= site_url('auth/connexion/client') ?>" class="text-decoration-none">
                <div class="card carte-choix shadow p-4 text-center h-100">
                    <div class="icone-choix mb-3">🔑</div>
                    <h3 class="fw-semibold">Espace Locataire</h3>
                    <p class="text-muted">Recherchez un logement et déposez votre demande de location.</p>
                    <span class="btn btn-primary mt-2">Accéder à l'espace locataire</span>
                </div>
            </a>
        </div>

        <div class="col-md-5">
            <a href="<?= site_url('auth/connexion/proprietaire') ?>" class="text-decoration-none">
                <div class="card carte-choix shadow p-4 text-center h-100">
                    <div class="icone-choix mb-3">🏠</div>
                    <h3 class="fw-semibold">Espace Propriétaire</h3>
                    <p class="text-muted">Gérez vos biens et traitez les demandes de location reçues.</p>
                    <span class="btn btn-outline-primary mt-2">Accéder à l'espace propriétaire</span>
                </div>
            </a>
        </div>
    </div>
</div>
</body>
</html>