<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalTech Bail Madagascar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="landing-page">
<div class="container">
    <div class="text-center landing-title mb-5">
        <span class="landing-kicker">PLATEFORME JURIDIQUE IMMOBILIÈRE</span>
        <h1 class="fw-bold">LegalTech — Contrat de bail</h1>
        <p class="lead">Une gestion claire et sereine de la location à Madagascar</p>
    </div>

    <div class="row landing-options justify-content-center g-4">
        <div class="col-md-5 d-flex">
            <a href="<?= site_url('auth/connexion/client') ?>" class="landing-card-link text-decoration-none">
                <div class="card carte-choix shadow p-4 text-center h-100">
                    <div class="icone-choix mb-3">LOCATAIRE</div>
                    <h3 class="fw-semibold">Espace Locataire</h3>
                    <p class="text-muted">Recherchez un logement et déposez votre demande de location.</p>
                    <span class="btn btn-primary landing-action">Accéder à l’espace locataire</span>
                </div>
            </a>
        </div>

        <div class="col-md-5 d-flex">
            <a href="<?= site_url('auth/connexion/proprietaire') ?>" class="landing-card-link text-decoration-none">
                <div class="card carte-choix shadow p-4 text-center h-100">
                    <div class="icone-choix mb-3">PROPRIÉTAIRE</div>
                    <h3 class="fw-semibold">Espace Propriétaire</h3>
                    <p class="text-muted">Gérez vos biens et traitez les demandes de location reçues.</p>
                    <span class="btn btn-outline-primary landing-action">Accéder à l’espace propriétaire</span>
                </div>
            </a>
        </div>
    </div>
</div>
</body>
</html>