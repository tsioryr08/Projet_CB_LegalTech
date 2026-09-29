<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrats — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container py-4">
    <h2>Contrats</h2>
    <table class="table table-bordered bg-white align-middle">
        <thead>
            <tr>
                <th>Numéro</th>
                <th>Maison / Locataire</th>
                <th>Usage</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contrats as $contrat): ?>
            <tr>
                <td><?= esc($contrat['numero_contrat']) ?></td>
                <td><?= esc($contrat['maison_titre'] ?? '') ?><br><small class="text-muted"><?= esc(trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? ''))) ?></small></td>
                <td><?= esc($contrat['type_code'] ?? '') ?></td>
                <td><span class="badge bg-secondary"><?= esc($contrat['statut']) ?></span></td>
                <td>
                    <a class="btn btn-sm btn-primary" href="<?= site_url('proprietaire/contrats/' . $contrat['id_contrat']) ?>">Consulter</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('contrats/' . $contrat['id_contrat'] . '/pdf') ?>">PDF</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('contrats/' . $contrat['id_contrat'] . '/fiche-fiscale') ?>">Fiche fiscale</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($contrats)): ?>
            <tr><td colspan="5" class="text-center text-muted">Aucun contrat généré pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
