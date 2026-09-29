<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes avenants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_' . (session('role') === 'proprietaire' ? 'proprietaire' : 'client')) ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Mes avenants</h1>
            <p class="text-muted mb-0">Recherchez et consultez les avenants liés à vos contrats.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url($role === 'proprietaire' ? 'proprietaire/contrats' : 'client/mes-demandes') ?>">Retour</a>
    </div>

    <form method="get" action="<?= esc($urlListe) ?>" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label">Recherche</label>
                <input id="q" name="q" class="form-control" value="<?= esc($recherche) ?>" placeholder="Contrat, logement, personne, type ou champ modifié">
            </div>
            <div class="col-md-2">
                <label for="statut" class="form-label">Statut</label>
                <select id="statut" name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <?php foreach (['propose' => 'Proposé', 'signe_bailleur' => 'Signé par le propriétaire', 'actif' => 'Actif', 'refuse' => 'Refusé', 'annule' => 'Annulé'] as $valeur => $libelle): ?>
                        <option value="<?= esc($valeur) ?>" <?= $statutFiltre === $valeur ? 'selected' : '' ?>><?= esc($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="">Tous les types</option>
                    <?php foreach (['modification_loyer' => 'Modification du loyer', 'prolongation_duree' => 'Prolongation de durée', 'autorisation_sous_location' => 'Sous-location', 'modification_caution' => 'Modification de caution', 'ajout_retrait_occupant' => 'Occupant', 'autre' => 'Autre'] as $valeur => $libelle): ?>
                        <option value="<?= esc($valeur) ?>" <?= $typeFiltre === $valeur ? 'selected' : '' ?>><?= esc($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit">Filtrer</button>
                <a class="btn btn-outline-secondary" href="<?= esc($urlListe) ?>">Effacer</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted"><?= count($avenants) ?> résultat(s)</span>
    </div>
    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>Avenant</th><th>Contrat / logement</th><th>Partie</th><th>Modification</th><th>Statut</th><th>Date d’effet</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($avenants as $avenant): ?>
                <tr>
                    <td><strong>#<?= esc($avenant['numero_avenant']) ?></strong><br><small class="text-muted"><?= esc($avenant['type_avenant']) ?></small></td>
                    <td><a href="<?= site_url(($role === 'proprietaire' ? 'proprietaire' : 'client') . '/contrats/' . $avenant['id_contrat']) ?>"><?= esc($avenant['numero_contrat']) ?></a><br><small class="text-muted"><?= esc($avenant['maison_titre']) ?></small></td>
                    <td><?= esc($role === 'proprietaire' ? trim(($avenant['client_prenoms'] ?? '') . ' ' . ($avenant['client_nom'] ?? '')) : trim(($avenant['proprietaire_prenoms'] ?? '') . ' ' . ($avenant['proprietaire_nom'] ?? ''))) ?></td>
                    <td><?= esc($avenant['champ_modifie']) ?><br><small class="text-muted"><?= esc($avenant['ancienne_valeur'] ?? '—') ?> → <?= esc($avenant['nouvelle_valeur']) ?></small></td>
                    <td><span class="badge bg-secondary"><?= esc($avenant['statut']) ?></span></td>
                    <td><?= esc($avenant['date_effet']) ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?= site_url('avenants/' . $avenant['id_avenant']) ?>">Consulter</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($avenants)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Aucun avenant ne correspond à ces critères.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
