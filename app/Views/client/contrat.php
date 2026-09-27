<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon contrat <?= esc($numero_contrat) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <?php if (session('succes')): ?><div class="alert alert-success"><?= esc(session('succes')) ?></div><?php endif; ?>
    <?php if (session('erreur')): ?><div class="alert alert-danger"><?= esc(session('erreur')) ?></div><?php endif; ?>

    <h2>Mon contrat</h2>
    <p class="text-muted">Lecture seule, téléchargement et signature finale.</p>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <strong><?= esc($numero_contrat) ?></strong><br>
                <span class="text-muted"><?= esc($maison_titre ?? '') ?></span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?= site_url('contrats/' . $id_contrat . '/pdf') ?>">PDF</a>
                <a class="btn btn-outline-secondary" href="<?= site_url('contrats/' . $id_contrat . '/fiche-fiscale') ?>">Fiche fiscale</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>Statut :</strong> <?= esc($statut) ?></p>
            <p class="mb-1"><strong>Propriétaire :</strong> <?= esc(trim(($proprietaire_prenoms ?? '') . ' ' . ($proprietaire_nom ?? ''))) ?></p>
            <p class="mb-1"><strong>Usage :</strong> <?= esc($type_code ?? '') ?></p>
            <p class="mb-0"><strong>Hash :</strong> <?= esc($contenu_hash_sha256) ?></p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Signatures</h5>
            <ul class="mb-0">
                <?php foreach (($signatures ?? []) as $signature): ?>
                    <li><?= esc($signature['role_signataire']) ?> - <?= esc($signature['nom_affiche']) ?> - <?= esc($signature['signe_le']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Avenants</h5>
            <?php if (! empty($avenants)): ?>
                <div class="list-group">
                    <?php foreach ($avenants as $avenant): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Avenant #<?= esc($avenant['numero_avenant']) ?></strong> - <?= esc($avenant['type_avenant']) ?><br>
                                <small class="text-muted"><?= esc($avenant['champ_modifie']) ?> : <?= esc($avenant['ancienne_valeur'] ?? '—') ?> → <?= esc($avenant['nouvelle_valeur']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary mb-2"><?= esc($avenant['statut']) ?></span><br>
                                <?php if ($avenant['statut'] === 'signe_bailleur'): ?>
                                    <form method="post" action="<?= site_url('client/avenants/' . $avenant['id_avenant'] . '/signer') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-primary">Signer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Aucun avenant en attente.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($statut === 'signe_bailleur'): ?>
        <form method="post" action="<?= site_url('contrats/' . $id_contrat . '/signer-locataire') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">J'ai lu et j'accepte</button>
        </form>
    <?php else: ?>
        <button class="btn btn-secondary" disabled>Signature locataire disponible après signature du bailleur</button>
    <?php endif; ?>
</div>
</body>
</html>
