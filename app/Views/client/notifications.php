<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notif-non-lue { border-left: 4px solid #0d6efd; background: #f0f6ff; }
        .notif-lue { border-left: 4px solid transparent; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Locataire</span>
        <div>
            <a href="<?= site_url('client/catalogue') ?>" class="btn btn-outline-light btn-sm me-2">Catalogue</a>
            <a href="<?= site_url('client/mes-demandes') ?>" class="btn btn-outline-light btn-sm me-2">Mes demandes</a>
            <a href="<?= site_url('client/profil') ?>" class="btn btn-outline-light btn-sm me-2">Mon profil</a>
            <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container pb-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Notifications</h2>
        <?php if (! empty($notifications)) : ?>
            <form method="post" action="<?= site_url('client/notifications/tout-marquer-lu') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Tout marquer comme lu</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>

    <?php if (empty($notifications)) : ?>
        <div class="alert alert-info">Vous n'avez aucune notification pour le moment.</div>
    <?php else : ?>
        <div class="list-group">
            <?php foreach ($notifications as $n) : ?>
                <div class="list-group-item <?= $n['lue'] ? 'notif-lue' : 'notif-non-lue' ?> d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-1"><?= esc($n['message']) ?></p>
                        <small class="text-muted">
                            <?= date('d/m/Y à H:i', strtotime($n['cree_le'])) ?>
                        </small>
                    </div>
                    <?php if (! $n['lue']) : ?>
                        <form method="post" action="<?= site_url('client/notifications/' . $n['id_notification'] . '/lu') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-link">Marquer comme lu</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>