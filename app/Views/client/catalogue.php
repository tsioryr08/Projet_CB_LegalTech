<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .carte-maison img, .carte-maison .placeholder-photo {
            height: 180px; object-fit: cover; background: #e9ecef;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Locataire</span>
        <div>
            <a href="<?= site_url('client/profil') ?>" class="btn btn-outline-light btn-sm me-2">Mon profil</a>
            <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <h2>Bienvenue, <?= esc($nom) ?> 👋</h2>
    <p class="text-muted mb-4">Parcourez les maisons disponibles à la location.</p>

    <!-- Filtre par ville -->
    <form method="get" action="<?= site_url('client/catalogue') ?>" class="row g-2 mb-4">
        <div class="col-auto">
            <select name="ville" class="form-select" onchange="this.form.submit()">
                <option value="">Toutes les villes</option>
                <?php foreach ($villes as $ville) : ?>
                    <option value="<?= $ville['id_ville'] ?>" <?= $idVilleActif == $ville['id_ville'] ? 'selected' : '' ?>>
                        <?= esc($ville['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($idVilleActif) : ?>
            <div class="col-auto">
                <a href="<?= site_url('client/catalogue') ?>" class="btn btn-outline-secondary">Réinitialiser</a>
            </div>
        <?php endif; ?>
    </form>

    <?php if (empty($maisons)) : ?>
        <div class="alert alert-info">
            Aucune maison disponible pour le moment<?= $idVilleActif ? ' dans cette ville' : '' ?>.
            Revenez bientôt, de nouveaux biens seront ajoutés par les propriétaires.
        </div>
    <?php else : ?>
        <div class="row g-4">
            <?php foreach ($maisons as $maison) : ?>
                <div class="col-md-4">
                    <div class="card carte-maison shadow-sm h-100">
                        <div class="placeholder-photo d-flex align-items-center justify-content-center text-muted">
                            Pas de photo
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= esc($maison['titre']) ?></h5>
                            <p class="card-text text-muted mb-1">
                                📍 <?= esc($maison['nom_ville']) ?> — <?= esc(ucfirst($maison['type_bien'])) ?>
                            </p>
                            <p class="card-text mb-1"><?= $maison['nb_chambres'] ?> chambre(s)</p>
                            <p class="card-text fw-bold">
                                <?= number_format($maison['loyer_mensuel'], 0, ',', ' ') ?> Ar / mois
                            </p>
                            <a href="<?= site_url('client/maison/' . $maison['id_maison']) ?>"
                               class="btn btn-primary btn-sm w-100">Voir le détail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>