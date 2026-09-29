<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Photos — <?= esc($maison['titre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container">
    <h2>Photos — <?= esc($maison['titre']) ?></h2>

    <?php if (session('succes')): ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')): ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <form action="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/photos') ?>" method="post" enctype="multipart/form-data" class="row g-2 mb-4">
        <?= csrf_field() ?>
        <div class="col-auto">
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control" required>
        </div>
        <div class="col-auto">
            <input type="number" name="ordre" class="form-control input-order" placeholder="Ordre" value="0">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
    </form>

    <div class="row">
        <?php foreach ($photos as $photo): ?>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <img src="<?= base_url($photo['chemin']) ?>" class="card-img-top" alt="Photo maison">
                    <div class="card-body text-center">
                        <form action="<?= site_url('proprietaire/maisons/' . $maison['id_maison'] . '/photos/' . $photo['id_photo'] . '/supprimer') ?>" method="post" onsubmit="return confirm('Supprimer cette photo ?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($photos)): ?>
            <p class="text-muted">Aucune photo pour le moment.</p>
        <?php endif; ?>
    </div>

    <a href="<?= site_url('proprietaire/maisons') ?>" class="btn btn-outline-secondary">Retour à la liste</a>
</div>
</body>
</html>
