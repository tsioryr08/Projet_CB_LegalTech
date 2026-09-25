<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($maison['titre']) ?> — Fiche détail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <a href="<?= site_url('client/catalogue') ?>" class="text-decoration-none">&larr; Retour au catalogue</a>

    <div class="row mt-3">
        <div class="col-md-7">
            <?php if (! empty($photos)) : ?>
                <div id="carouselPhotos" class="carousel slide mb-3">
                    <div class="carousel-inner rounded shadow-sm">
                        <?php foreach ($photos as $i => $photo) : ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <img src="<?= esc($photo['chemin']) ?>" class="d-block w-100" style="height:380px;object-fit:cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($photos) > 1) : ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPhotos" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPhotos" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="bg-secondary-subtle rounded shadow-sm d-flex align-items-center justify-content-center text-muted mb-3"
                     style="height:380px;">
                    Aucune photo disponible pour le moment
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3><?= esc($maison['titre']) ?></h3>
                    <p class="text-muted mb-2">
                        📍 <?= esc($maison['nom_ville']) ?> (<?= esc($maison['region_ville']) ?>)
                    </p>
                    <p class="fs-4 fw-bold text-primary">
                        <?= number_format($maison['loyer_mensuel'], 0, ',', ' ') ?> Ar / mois
                    </p>

                    <ul class="list-unstyled mb-3">
                        <li>🏠 Type : <?= esc(ucfirst($maison['type_bien'])) ?></li>
                        <li>🛏️ Chambres : <?= $maison['nb_chambres'] ?></li>
                        <?php if ($maison['superficie_m2']) : ?>
                            <li>📐 Superficie : <?= $maison['superficie_m2'] ?> m²</li>
                        <?php endif; ?>
                        <li>📋 Usage autorisé : <?= esc(ucfirst($maison['usage_autorise'])) ?></li>
                    </ul>

                    <?php if ($maison['description']) : ?>
                        <p><?= nl2br(esc($maison['description'])) ?></p>
                    <?php endif; ?>

                    <hr>

                    <h6 class="text-muted">Historique du bien</h6>
                    <?php if ($historique) : ?>
                        <ul class="list-unstyled small text-muted">
                            <li>👥 Anciens locataires : <?= $historique['nb_anciens_locataires'] ?></li>
                            <li>⚖️ Litiges déclarés : <?= $historique['nb_litiges_declares'] ?></li>
                            <li>💸 Impayés déclarés : <?= $historique['nb_impayes_declares'] ?></li>
                        </ul>
                    <?php else : ?>
                        <p class="small text-muted">Aucun historique disponible pour ce bien.</p>
                    <?php endif; ?>

                    <button type="button" class="btn btn-primary w-100 mt-2" disabled title="Fonctionnalité C3, à venir">
                        Demander la location
                    </button>
                    <div class="form-text text-center">Le bouton de demande sera activé au lot C3.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>