<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer le contrat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container py-4">
    <h2>Génération du contrat</h2>
    <p class="text-muted">Maison : <?= esc($maison['titre']) ?> | Locataire : <?= esc(trim(($client['prenoms'] ?? '') . ' ' . ($client['nom'] ?? ''))) ?></p>

    <?php if (! empty($erreur)): ?>
        <div class="alert alert-danger"><?= esc($erreur) ?></div>
    <?php endif; ?>

    <?php if (! empty($analyse['regles'])): ?>
        <div class="alert alert-warning">
            <strong>Points à contrôler :</strong>
            <ul class="mb-0">
                <?php foreach ($analyse['regles'] as $regle): ?>
                    <li><?= esc($regle['message_affiche'] ?? $regle['libelle'] ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('proprietaire/demandes/' . $demande['id_demande'] . '/contrat') ?>" class="card card-body shadow-sm">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Usage</label>
                <select name="usage_contrat" class="form-select" required>
                    <?php foreach (['habitation' => 'Habitation', 'commercial' => 'Commercial', 'mixte' => 'Mixte'] as $code => $label): ?>
                        <option value="<?= esc($code) ?>" <?= ($usageParDefaut ?? 'habitation') === $code ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nombre d'occupants</label>
                <input type="number" name="nb_occupants" class="form-control" min="1" value="<?= esc($donneesLegaltech['nb_occupants'] ?? 1) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Situation du locataire</label>
                <select name="situation_client" class="form-select" required>
                    <?php foreach (['libre' => 'Libre', 'mineur' => 'Mineur', 'prevenu' => 'Prévenu', 'condamne' => 'Condamné'] as $code => $label): ?>
                        <option value="<?= esc($code) ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Activité déclarée</label>
                <input type="text" name="activite_declaree" class="form-control" value="<?= esc($donneesLegaltech['activite_declaree'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date de début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= esc(date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date de fin</label>
                <input type="date" name="date_fin" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dépôt de garantie</label>
                <input type="number" step="0.01" name="depot_garantie" class="form-control" value="0">
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Générer le contrat</button>
            <a href="<?= site_url('proprietaire/demandes') ?>" class="btn btn-outline-secondary">Retour</a>
        </div>
    </form>
</div>
</body>
</html>
