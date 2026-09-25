<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Locataire</span>
        <div>
            <a href="<?= site_url('client/catalogue') ?>" class="btn btn-outline-light btn-sm me-2">Catalogue</a>
            <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container pb-5" style="max-width: 700px;">
    <h2 class="mb-4">Mon profil</h2>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
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

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= site_url('client/profil') ?>">
                <?= csrf_field() ?>

                <h5 class="mb-3 text-muted">Identité</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" class="form-control"
                               value="<?= esc(old('nom', $utilisateur['nom'])) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prénoms</label>
                        <input type="text" name="prenoms" class="form-control"
                               value="<?= esc(old('prenoms', $utilisateur['prenoms'] ?? '')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control"
                               value="<?= esc(old('telephone', $utilisateur['telephone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" value="<?= esc($utilisateur['email']) ?>" disabled>
                        <div class="form-text">L'e-mail ne peut pas être modifié ici.</div>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3 text-muted">
                    Pièce d'identité
                    <span class="badge bg-secondary fw-normal">requis avant de louer</span>
                </h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Numéro de CIN</label>
                        <input type="text" name="cin_numero" class="form-control" maxlength="12"
                               placeholder="12 chiffres"
                               value="<?= esc(old('cin_numero', $utilisateur['cin_numero'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sexe</label>
                        <select name="sexe" class="form-select">
                            <option value="">-- Sélectionner --</option>
                            <?php $sexeActuel = old('sexe', $utilisateur['sexe'] ?? ''); ?>
                            <option value="M" <?= $sexeActuel === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $sexeActuel === 'F' ? 'selected' : '' ?>>Féminin</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de délivrance de la CIN</label>
                        <input type="date" name="cin_date_delivrance" class="form-control"
                               value="<?= esc(old('cin_date_delivrance', $utilisateur['cin_date_delivrance'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lieu de délivrance</label>
                        <input type="text" name="cin_lieu_delivrance" class="form-control"
                               value="<?= esc(old('cin_lieu_delivrance', $utilisateur['cin_lieu_delivrance'] ?? '')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control"
                               value="<?= esc(old('date_naissance', $utilisateur['date_naissance'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Profession</label>
                        <input type="text" name="profession" class="form-control"
                               value="<?= esc(old('profession', $utilisateur['profession'] ?? '')) ?>">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3 text-muted">
                    Informations fiscales
                    <span class="badge bg-secondary fw-normal">si usage commercial ou mixte</span>
                </h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIF</label>
                        <input type="text" name="nif" class="form-control"
                               value="<?= esc(old('nif', $utilisateur['nif'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">STAT</label>
                        <input type="text" name="stat" class="form-control"
                               value="<?= esc(old('stat', $utilisateur['stat'] ?? '')) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Enregistrer mon profil</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>