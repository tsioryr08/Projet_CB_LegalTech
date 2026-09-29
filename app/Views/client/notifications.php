<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_client') ?>

<div class="container pb-5 page-narrow">
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