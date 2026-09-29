<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique — <?= esc($maison['titre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container">
    <h2>Historique agrégé — <?= esc($maison['titre']) ?></h2>
    <p class="text-muted">Compteurs anonymes uniquement — aucune donnée personnelle d'ancien locataire.</p>

    <?php if (session('succes')): ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>

    <form action="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/historique') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Nombre d'anciens locataires</label>
            <input type="number" min="0" name="nb_anciens_locataires" class="form-control"
                   value="<?= esc((string) ($historique['nb_anciens_locataires'] ?? 0)) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre de litiges déclarés</label>
            <input type="number" min="0" name="nb_litiges_declares" class="form-control"
                   value="<?= esc((string) ($historique['nb_litiges_declares'] ?? 0)) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre d'impayés déclarés</label>
            <input type="number" min="0" name="nb_impayes_declares" class="form-control"
                   value="<?= esc((string) ($historique['nb_impayes_declares'] ?? 0)) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= site_url('proprietaire/maisons') ?>" class="btn btn-outline-secondary">Retour</a>
    </form>
</div>
</body>
</html>
