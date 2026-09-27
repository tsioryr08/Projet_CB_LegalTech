<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $maison ? 'Modifier' : 'Ajouter' ?> une maison</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Propriétaire</span>
        <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <h2><?= $maison ? 'Modifier la maison' : 'Ajouter une maison' ?></h2>

    <?php if (isset($validation)): ?>
        <div class="alert alert-danger">
            <?php foreach ((array) $validation as $erreur): ?>
                <div><?= esc($erreur) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="<?= $maison ? site_url('proprietaire/maisons/' . $maison['id_maison'] . '/modifier') : site_url('proprietaire/maisons/ajouter') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Ville</label>
            <select name="id_ville" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($villes as $ville): ?>
                    <option value="<?= $ville['id_ville'] ?>" <?= isset($maison['id_ville']) && (int) $maison['id_ville'] === (int) $ville['id_ville'] ? 'selected' : '' ?>>
                        <?= esc($ville['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Titre</label>
            <input type="text" name="titre" class="form-control" required
                   value="<?= esc(old('titre', $maison['titre'] ?? '')) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Adresse</label>
            <input type="text" name="adresse" class="form-control" required
                   value="<?= esc(old('adresse', $maison['adresse'] ?? '')) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= esc(old('description', $maison['description'] ?? '')) ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Type de bien</label>
                <select name="type_bien" class="form-select" required>
                    <?php foreach (['villa', 'appartement', 'studio', 'local_commercial', 'autre'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($maison['type_bien'] ?? '') === $type ? 'selected' : '' ?>>
                            <?= esc($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Nombre de chambres</label>
                <input type="number" min="1" name="nb_chambres" class="form-control"
                       value="<?= esc((string) ($maison['nb_chambres'] ?? old('nb_chambres', 1))) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Superficie (m²)</label>
                <input type="number" step="0.01" name="superficie_m2" class="form-control"
                       value="<?= esc(old('superficie_m2', $maison['superficie_m2'] ?? '')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Loyer mensuel (Ar)</label>
                <input type="number" step="0.01" name="loyer_mensuel" class="form-control" required
                       value="<?= esc(old('loyer_mensuel', $maison['loyer_mensuel'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Valeur de l'immeuble (Ar)</label>
                <input type="number" step="0.01" name="valeur_immeuble" class="form-control"
                       value="<?= esc(old('valeur_immeuble', $maison['valeur_immeuble'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date de construction</label>
                <input type="date" name="date_construction" class="form-control"
                       value="<?= esc(old('date_construction', $maison['date_construction'] ?? '')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">N° titre foncier</label>
                <input type="text" name="titre_foncier_numero" class="form-control"
                       value="<?= esc(old('titre_foncier_numero', $maison['titre_foncier_numero'] ?? '')) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Usage autorisé</label>
                <select name="usage_autorise" class="form-select">
                    <?php foreach (['habitation', 'commercial', 'mixte'] as $usage): ?>
                        <option value="<?= $usage ?>" <?= ($maison['usage_autorise'] ?? 'habitation') === $usage ? 'selected' : '' ?>>
                            <?= esc($usage) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= site_url('proprietaire/maisons') ?>" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
