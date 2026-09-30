<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes demandes — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_client') ?>

<div class="container py-4 pb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <p class="text-muted text-uppercase small fw-semibold mb-1">Suivi de location</p>
            <h1 class="h2 mb-1">Mes demandes</h1>
            <p class="text-muted mb-0">Consultez l’avancement de vos demandes de location.</p>
        </div>
        <span class="badge bg-secondary fs-6"><?= count($demandes) ?> résultat(s)</span>
    </div>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')) : ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= site_url('client/mes-demandes') ?>" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5 col-md-6">
                <label for="q" class="form-label">Mots-clés</label>
                <input id="q" name="q" type="search" class="form-control" value="<?= esc($recherche) ?>" placeholder="Logement, ville ou motif de refus…">
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
                <label for="du" class="form-label">Demandée à partir du</label>
                <input id="du" name="du" type="date" class="form-control" value="<?= esc($dateDu) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="au" class="form-label">Jusqu’au</label>
                <input id="au" name="au" type="date" class="form-control" value="<?= esc($dateAu) ?>">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= site_url('client/mes-demandes') ?>">Effacer les filtres</a>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </div>
        </div>
    </form>

    <?php
    $badges = ['envoyee' => 'bg-warning text-dark', 'validee' => 'bg-success', 'refusee' => 'bg-danger', 'annulee' => 'bg-secondary'];
    $libelles = ['envoyee' => 'En attente', 'validee' => 'Validée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'];
    ?>

    <?php if (empty($demandes)) : ?>
        <div class="alert alert-info">
            <?= $filtresActifs ? 'Aucune demande ne correspond à ces critères.' : 'Vous n’avez encore fait aucune demande de location.' ?>
        </div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th>Logement</th><th>Ville</th><th>Loyer</th><th>Statut</th><th>Contrat</th><th>Date de demande</th><th>Suite</th>
                </tr></thead>
                <tbody>
                <?php foreach ($demandes as $d) : ?>
                    <tr>
                        <td><strong><?= esc($d['titre']) ?></strong></td>
                        <td><?= esc($d['nom_ville']) ?></td>
                        <td><?= number_format((float) $d['loyer_mensuel'], 0, ',', ' ') ?> Ar</td>
                        <td>
                            <span class="badge <?= $badges[$d['statut']] ?? 'bg-secondary' ?>"><?= $libelles[$d['statut']] ?? esc($d['statut']) ?></span>
                            <?php if ($d['statut'] === 'refusee' && ! empty($d['motif_refus'])) : ?>
                                <div class="small text-muted mt-1">Motif : <?= esc($d['motif_refus']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($d['contrat'])) : ?>
                                <a href="<?= site_url('client/contrats/' . $d['contrat']['id_contrat']) ?>" class="btn btn-sm btn-outline-primary">Mon contrat</a>
                            <?php else : ?><span class="text-muted small">—</span><?php endif; ?>
                        </td>
                        <td><?= esc(date('d/m/Y à H:i', strtotime($d['date_demande']))) ?></td>
                        <td>
                            <?php if ($d['statut'] === 'validee') : ?>
                                <a href="<?= site_url('client/dossier/' . $d['id_demande']) ?>" class="btn btn-sm btn-primary">Compléter le dossier</a>
                            <?php else : ?><span class="text-muted small">—</span><?php endif; ?>
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
