<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier de location — <?= esc($demande['titre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_client') ?>

<div class="container pb-5 page-narrow">
    <a href="<?= site_url('client/mes-demandes') ?>" class="text-decoration-none">&larr; Retour à mes demandes</a>

    <h2 class="mt-3 mb-1">Dossier de location</h2>
    <p class="text-muted mb-4">Pour : <?= esc($demande['titre']) ?></p>

    <?php if (session('erreur')) : ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>
    <?php if (session('errors')) : ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session('errors') as $erreur) : ?>
                    <li><?= esc($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($dossierExistant && $dossierExistant['statut_validation_legale'] === 'alerte') : ?>
        <div class="alert alert-warning">
            Votre dossier précédent a été enregistré avec une alerte. Vous pouvez le corriger ci-dessous.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= site_url('client/dossier/' . $demande['id_demande']) ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Nombre d'occupants</label>
                    <input type="number" name="nb_occupants" class="form-control" min="1" max="50"
                           value="<?= esc(old('nb_occupants', $dossierExistant['nb_occupants'] ?? 1)) ?>" required>
                    <div class="form-text">Ce bien compte <?= $demande['nb_chambres'] ?> chambre(s).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usage prévu du logement</label>
                    <?php $usageActuel = old('usage_declare', $dossierExistant['usage_declare'] ?? 'habitation'); ?>
                    <select name="usage_declare" class="form-select" required>
                        <option value="habitation" <?= $usageActuel === 'habitation' ? 'selected' : '' ?>>Habitation uniquement</option>
                        <option value="commercial" <?= $usageActuel === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                        <option value="mixte" <?= $usageActuel === 'mixte' ? 'selected' : '' ?>>Mixte (habitation + activité)</option>
                    </select>
                    <div class="form-text">Usage autorisé pour ce bien : <?= esc(ucfirst($demande['usage_autorise'])) ?>.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Activité déclarée (si commercial ou mixte)</label>
                    <input type="text" name="activite_declaree" class="form-control" placeholder="ex. épicerie"
                           value="<?= esc(old('activite_declaree', $dossierExistant['activite_declaree'] ?? '')) ?>">
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-2">Enregistrer mon dossier</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>