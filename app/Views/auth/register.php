<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — <?= esc(ucfirst($role)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 520px;">

    <a href="<?= site_url('auth/connexion/' . $role) ?>" class="text-decoration-none">&larr; Retour à la connexion</a>

    <div class="card shadow-sm mt-3">
        <div class="card-body p-4">
            <h3 class="mb-3 text-center">
                Créer un compte — <?= $role === 'client' ? 'Locataire' : 'Propriétaire' ?>
            </h3>

            <?php if (session('errors')) : ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach (session('errors') as $erreur) : ?>
                            <li><?= esc($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('auth/inscription/' . $role) ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control"
                           value="<?= esc(old('nom')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Prénoms</label>
                    <input type="text" name="prenoms" class="form-control"
                           value="<?= esc(old('prenoms')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Adresse e-mail</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= esc(old('email')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control"
                           value="<?= esc(old('telephone')) ?>" placeholder="03X XX XXX XX">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="mot_de_passe" class="form-control" required minlength="6">
                    <div class="form-text">6 caractères minimum.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>