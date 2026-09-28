<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Propriétaire</span>
        <div class="navbar-nav me-auto">
            <a href="<?= site_url('proprietaire/tableau-de-bord') ?>" class="nav-link text-white">Tableau de bord</a>
            <a href="<?= site_url('proprietaire/maisons') ?>" class="nav-link text-white">Mes maisons</a>
            <a href="<?= site_url('proprietaire/demandes') ?>" class="nav-link text-white">Demandes</a>
            <a href="<?= site_url('proprietaire/profil') ?>" class="nav-link text-white">Mon profil</a>
        </div>
        <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <h2>Bienvenue, <?= esc($nom) ?> 👋</h2>
    <p class="text-muted">Gérez vos biens, vos demandes de location et vos contrats depuis cet espace.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mes maisons</h5>
                    <p class="card-text text-muted">Ajouter, modifier, gérer les photos et l'historique de vos biens.</p>
                    <a href="<?= site_url('proprietaire/maisons') ?>" class="btn btn-primary btn-sm">Gérer mes maisons</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Demandes reçues</h5>
                    <p class="card-text text-muted">Valider ou refuser les demandes de location sur vos biens.</p>
                    <a href="<?= site_url('proprietaire/demandes') ?>" class="btn btn-primary btn-sm">Voir les demandes</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Contrats</h5>
                    <p class="card-text text-muted">Générer, signer et suivre vos contrats et avenants.</p>
                    <a href="<?= site_url('proprietaire/contrats') ?>" class="btn btn-primary btn-sm">Voir les contrats</a>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        Contrats actifs : <?= esc($contratsActifs) ?> | En attente de signature : <?= esc($contratsAAttention) ?>
    </div>
</div>
</body>
</html>