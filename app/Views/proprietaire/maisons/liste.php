<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes maisons — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Mes maisons</h2>
        <a href="<?= site_url('proprietaire/maisons/ajouter') ?>" class="btn btn-primary">Ajouter une maison</a>
    </div>

    <?php if (session('succes')): ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')): ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Loyer (Ar)</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($maisons as $maison): ?>
            <tr>
                <td><?= esc($maison['titre']) ?></td>
                <td><?= esc($maison['type_bien']) ?></td>
                <td><?= number_format((float) $maison['loyer_mensuel'], 0, ',', ' ') ?></td>
                <td>
                    <form action="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/statut') ?>" method="post" class="d-flex gap-1">
                        <?= csrf_field() ?>
                        <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (['disponible', 'en_attente', 'loue', 'archive'] as $statut): ?>
                                <option value="<?= $statut ?>" <?= $statut === $maison['statut'] ? 'selected' : '' ?>>
                                    <?= esc($statut) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="text-nowrap">
                    <a href="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/modifier') ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <a href="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/photos') ?>" class="btn btn-sm btn-info">Photos</a>
                    <a href="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/historique') ?>" class="btn btn-sm btn-warning">Historique</a>
                    <form action="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/supprimer') ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette maison ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($maisons)): ?>
            <tr><td colspan="5" class="text-center text-muted">Aucune maison enregistrée.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
