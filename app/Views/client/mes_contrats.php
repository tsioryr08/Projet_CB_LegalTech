<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes contrats — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Locataire</span>
        <div>
            <a href="<?= site_url('client/catalogue') ?>" class="btn btn-outline-light btn-sm me-2">Catalogue</a>
            <a href="<?= site_url('client/mes-demandes') ?>" class="btn btn-outline-light btn-sm me-2">Mes demandes</a>
            <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <h2 class="mb-4">Mes contrats</h2>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>

    <?php if (empty($contrats)) : ?>
        <div class="alert alert-info">Vous n'avez aucun contrat pour le moment.</div>
    <?php else : ?>
        <?php
        $badges = [
            'genere'         => 'bg-secondary',
            'signe_bailleur' => 'bg-warning text-dark',
            'actif'          => 'bg-success',
            'resilie'        => 'bg-dark',
            'expire'         => 'bg-dark',
            'annule'         => 'bg-danger',
        ];
        ?>
        <div class="row g-3">
            <?php foreach ($contrats as $c) : ?>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5><?= esc($c['titre']) ?></h5>
                            <p class="text-muted mb-1"><?= esc($c['type_libelle']) ?> — N° <?= esc($c['numero_contrat']) ?></p>
                            <span class="badge <?= $badges[$c['statut']] ?? 'bg-secondary' ?> mb-2">
                                <?= esc(ucfirst(str_replace('_', ' ', $c['statut']))) ?>
                            </span>
                            <br>
                            <a href="<?= site_url('client/contrat/' . $c['id_contrat']) ?>" class="btn btn-sm btn-primary mt-2">
                                Voir le contrat
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>