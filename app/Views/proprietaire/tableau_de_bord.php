<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container">
    <h2>Bienvenue, <?= esc($nom) ?></h2>
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
                    <a href="<?= site_url('proprietaire/avenants') ?>" class="btn btn-outline-primary btn-sm ms-2">Voir les avenants</a>
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