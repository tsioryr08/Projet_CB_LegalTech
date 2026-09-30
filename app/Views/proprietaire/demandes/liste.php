<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes reçues — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <p class="text-muted text-uppercase small fw-semibold mb-1">Gestion locative</p>
            <h1 class="h2 mb-1">Demandes de location</h1>
            <p class="text-muted mb-0">Filtrez et traitez les demandes reçues pour vos biens.</p>
        </div>
        <span class="badge bg-secondary fs-6"><?= count($demandes) ?> résultat(s)</span>
    </div>

    <?php if (session('succes')): ?><div class="alert alert-success"><?= esc(session('succes')) ?></div><?php endif; ?>
    <?php if (session('erreur')): ?><div class="alert alert-danger"><?= esc(session('erreur')) ?></div><?php endif; ?>

    <form method="get" action="<?= site_url('proprietaire/demandes') ?>" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5 col-md-6">
                <label for="q" class="form-label">Mots-clés</label>
                <input id="q" name="q" type="search" class="form-control" value="<?= esc($recherche) ?>" placeholder="Nom, téléphone, logement, adresse…">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="statut" class="form-label">Statut</label>
                <select id="statut" name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <?php foreach (['envoyee' => 'En attente', 'validee' => 'Validée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'] as $valeur => $libelle): ?>
                        <option value="<?= esc($valeur) ?>" <?= $statutFiltre === $valeur ? 'selected' : '' ?>><?= esc($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="du" class="form-label">Reçue à partir du</label>
                <input id="du" name="du" type="date" class="form-control" value="<?= esc($dateDu) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="au" class="form-label">Jusqu’au</label>
                <input id="au" name="au" type="date" class="form-control" value="<?= esc($dateAu) ?>">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= site_url('proprietaire/demandes') ?>">Effacer les filtres</a>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </div>
        </div>
    </form>

    <?php $badges = ['envoyee' => 'bg-warning text-dark', 'validee' => 'bg-success', 'refusee' => 'bg-danger', 'annulee' => 'bg-secondary']; ?>
    <?php $libelles = ['envoyee' => 'En attente', 'validee' => 'Validée', 'refusee' => 'Refusée', 'annulee' => 'Annulée']; ?>
    <?php if (empty($demandes)): ?>
        <div class="alert alert-info"><?= $filtresActifs ? 'Aucune demande ne correspond à ces critères.' : 'Aucune demande reçue pour le moment.' ?></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Client</th><th>Logement</th><th>Date de demande</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($demandes as $demande): ?>
                    <tr>
                        <td>
                            <strong><?= esc(trim(($demande['client_prenoms'] ?? '') . ' ' . ($demande['client_nom'] ?? ''))) ?></strong><br>
                            <small class="text-muted"><?= esc($demande['client_telephone'] ?? '') ?></small>
                        </td>
                        <td><?= esc($demande['maison_titre']) ?><br><small class="text-muted"><?= esc($demande['maison_adresse'] ?? '') ?></small></td>
                        <td><?= esc(date('d/m/Y à H:i', strtotime($demande['date_demande']))) ?></td>
                        <td>
                            <span class="badge <?= $badges[$demande['statut']] ?? 'bg-secondary' ?>"><?= esc($libelles[$demande['statut']] ?? $demande['statut']) ?></span>
                            <?php if ($demande['statut'] === 'refusee' && ! empty($demande['motif_refus'])): ?>
                                <div class="small text-muted mt-1">Motif : <?= esc($demande['motif_refus']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="table-action-cell">
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
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
