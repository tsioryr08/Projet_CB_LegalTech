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
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <p class="text-muted text-uppercase small fw-semibold mb-1">Gestion locative</p>
            <h1 class="h2 mb-1">Contrats de bail</h1>
            <p class="text-muted mb-0">Retrouvez et filtrez les contrats générés pour vos biens.</p>
        </div>
        <span class="badge bg-secondary fs-6"><?= count($contrats) ?> résultat(s)</span>
    </div>

    <form method="get" action="<?= site_url('proprietaire/contrats') ?>" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label for="q" class="form-label">Mots-clés</label>
                <input id="q" name="q" type="search" class="form-control" value="<?= esc($recherche) ?>" placeholder="Numéro, locataire, logement, adresse…">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="statut" class="form-label">Statut</label>
                <select id="statut" name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <?php foreach (['genere' => 'Généré', 'signe_bailleur' => 'Signé par le propriétaire', 'actif' => 'Actif', 'resilie' => 'Résilié', 'expire' => 'Expiré', 'annule' => 'Annulé'] as $valeur => $libelle): ?>
                        <option value="<?= esc($valeur) ?>" <?= $statutFiltre === $valeur ? 'selected' : '' ?>><?= esc($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="type" class="form-label">Type de bail</label>
                <select id="type" name="type" class="form-select">
                    <option value="">Tous les types</option>
                    <?php foreach (['habitation' => 'Habitation', 'commercial' => 'Commercial', 'mixte' => 'Usage mixte'] as $valeur => $libelle): ?>
                        <option value="<?= esc($valeur) ?>" <?= $typeFiltre === $valeur ? 'selected' : '' ?>><?= esc($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="du" class="form-label">Généré à partir du</label>
                <input id="du" name="du" type="date" class="form-control" value="<?= esc($dateDu) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="au" class="form-label">Généré jusqu’au</label>
                <input id="au" name="au" type="date" class="form-control" value="<?= esc($dateAu) ?>">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= site_url('proprietaire/contrats') ?>">Effacer les filtres</a>
                <button class="btn btn-primary" type="submit">Rechercher</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contrat</th>
                    <th>Logement</th>
                    <th>Locataire</th>
                    <th>Type de bail</th>
                    <th>Généré le</th>
                    <th>Période du bail</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contrats as $contrat): ?>
                <?php
                $statutsLibelles = [
                    'genere' => 'Généré', 'signe_bailleur' => 'Signé par vous', 'actif' => 'Actif',
                    'resilie' => 'Résilié', 'expire' => 'Expiré', 'annule' => 'Annulé',
                ];
                $dateCreation = ! empty($contrat['cree_le']) ? date('d/m/Y', strtotime($contrat['cree_le'])) : '—';
                $dateDebut = ! empty($contrat['date_debut']) ? date('d/m/Y', strtotime($contrat['date_debut'])) : '—';
                $dateFin = ! empty($contrat['date_fin']) ? date('d/m/Y', strtotime($contrat['date_fin'])) : 'Durée indéterminée';
                ?>
                <tr>
                    <td><strong><?= esc($contrat['numero_contrat']) ?></strong></td>
                    <td><?= esc($contrat['maison_titre'] ?? '—') ?><br><small class="text-muted"><?= esc($contrat['maison_adresse'] ?? '') ?></small></td>
                    <td><?= esc(trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? ''))) ?></td>
                    <td><?= esc($contrat['type_libelle'] ?? ucfirst($contrat['type_code'] ?? '')) ?></td>
                    <td><?= esc($dateCreation) ?></td>
                    <td><?= esc($dateDebut) ?> – <?= esc($dateFin) ?></td>
                    <td><span class="badge bg-secondary"><?= esc($statutsLibelles[$contrat['statut']] ?? $contrat['statut']) ?></span></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-primary" href="<?= site_url('proprietaire/contrats/' . $contrat['id_contrat']) ?>">Consulter</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('contrats/' . $contrat['id_contrat'] . '/pdf') ?>">PDF</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($contrats)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">Aucun contrat ne correspond à ces critères.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
