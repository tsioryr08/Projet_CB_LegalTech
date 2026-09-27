<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demandes reçues — Espace Propriétaire</title>
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
    <h2 class="mb-3">Demandes de location reçues</h2>

    <?php if (session('succes')): ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')): ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <?php $badges = [
        'envoyee'  => 'bg-warning text-dark',
        'validee'  => 'bg-success',
        'refusee'  => 'bg-danger',
        'annulee'  => 'bg-secondary',
    ]; ?>

    <table class="table table-bordered bg-white align-middle">
        <thead>
            <tr>
                <th>Client</th>
                <th>Maison</th>
                <th>Date demande</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $demande): ?>
            <tr>
                <td>
                    <?= esc(trim(($demande['client_prenoms'] ?? '') . ' ' . $demande['client_nom'])) ?><br>
                    <small class="text-muted"><?= esc($demande['client_telephone'] ?? '') ?></small>
                </td>
                <td><?= esc($demande['maison_titre']) ?></td>
                <td><?= esc($demande['date_demande']) ?></td>
                <td>
                    <span class="badge <?= $badges[$demande['statut']] ?? 'bg-secondary' ?>">
                        <?= esc($demande['statut']) ?>
                    </span>
                    <?php if ($demande['statut'] === 'refusee' && ! empty($demande['motif_refus'])): ?>
                        <div class="small text-muted mt-1">Motif : <?= esc($demande['motif_refus']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="min-width: 260px;">
                    <?php if ($demande['statut'] === 'envoyee'): ?>
                        <form action="<?= site_url('proprietaire/demandes/' . $demande['id_demande'] . '/valider') ?>" method="post" class="d-inline" onsubmit="return confirm('Valider cette demande ?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-success mb-1">Valider</button>
                        </form>

                        <form action="<?= site_url('proprietaire/demandes/' . $demande['id_demande'] . '/refuser') ?>" method="post" class="d-flex gap-1 mt-1">
                            <?= csrf_field() ?>
                            <input type="text" name="motif_refus" class="form-control form-control-sm" placeholder="Motif de refus" required>
                            <button type="submit" class="btn btn-sm btn-danger">Refuser</button>
                        </form>
                    <?php elseif ($demande['statut'] === 'validee'): ?>
                        <a href="<?= site_url('proprietaire/demandes/' . $demande['id_demande'] . '/contrat') ?>" class="btn btn-sm btn-primary">Générer le contrat</a>
                    <?php else: ?>
                        <span class="text-muted small">Traitée le <?= esc($demande['date_traitement'] ?? '—') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($demandes)): ?>
            <tr><td colspan="5" class="text-center text-muted">Aucune demande reçue pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>