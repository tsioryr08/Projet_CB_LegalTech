<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= esc(ucfirst($role)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 480px;">

    <a href="<?= site_url('/') ?>" class="text-decoration-none">&larr; Retour à l'accueil</a>

    <div class="card shadow-sm mt-3">
        <div class="card-body p-4">
            <h3 class="mb-3 text-center">
                Connexion — <?= $role === 'client' ? 'Locataire' : 'Propriétaire' ?>
            </h3>

            <?php if (session('erreur')) : ?>
                <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
            <?php endif; ?>

            <?php if (session('succes')) : ?>
                <div class="alert alert-success"><?= esc(session('succes')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('auth/connexion/' . $role) ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Adresse e-mail</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= esc(old('email')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="mot_de_passe" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>

            <p class="text-center mt-3 mb-0">
                Pas encore de compte ?
                <a href="<?= site_url('auth/inscription/' . $role) ?>">Créer un compte</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>