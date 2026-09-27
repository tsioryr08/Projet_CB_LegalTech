<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat <?= esc($contrat['numero_contrat']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Locataire</span>
        <a href="<?= site_url('client/mes-contrats') ?>" class="btn btn-outline-light btn-sm">Mes contrats</a>
    </div>
</nav>

<div class="container pb-5" style="max-width: 750px;">
    <a href="<?= site_url('client/mes-contrats') ?>" class="text-decoration-none">&larr; Retour à mes contrats</a>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success mt-3"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')) : ?>
        <div class="alert alert-danger mt-3"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mt-3">
        <div class="card-body p-4">
            <h3><?= esc($contrat['titre']) ?></h3>
            <p class="text-muted"><?= esc($contrat['type_libelle']) ?> — N° <?= esc($contrat['numero_contrat']) ?></p>

            <ul class="list-unstyled">
                <li>💰 Loyer mensuel : <?= number_format($contrat['loyer_mensuel'], 0, ',', ' ') ?> Ar</li>
                <li>🔒 Dépôt de garantie : <?= number_format($contrat['depot_garantie'], 0, ',', ' ') ?> Ar</li>
                <li>📅 Début : <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?></li>
                <li>📅 Fin : <?= $contrat['date_fin'] ? date('d/m/Y', strtotime($contrat['date_fin'])) : 'Durée indéterminée' ?></li>
                <li>🏛️ Droit d'enregistrement : <?= $contrat['taux_enregistrement_applique'] ?>% (<?= number_format($contrat['montant_droit_enregistrement'], 0, ',', ' ') ?> Ar)</li>
            </ul>

            <?php if ($contrat['contenu_pdf_chemin']) : ?>
                <a href="<?= esc($contrat['contenu_pdf_chemin']) ?>" class="btn btn-outline-primary btn-sm mb-3" target="_blank">
                    📄 Télécharger le contrat (PDF)
                </a>
            <?php else : ?>
                <p class="text-muted small">Le PDF du contrat n'est pas encore disponible.</p>
            <?php endif; ?>

            <hr>

            <h6>Signature</h6>
            <?php if ($contrat['statut'] === 'genere') : ?>
                <p class="text-muted">En attente de la signature du propriétaire.</p>
            <?php elseif ($peutSigner) : ?>
                <form method="post" action="<?= site_url('client/contrat/' . $contrat['id_contrat'] . '/signer') ?>">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="accepte" required>
                        <label class="form-check-label" for="accepte">
                            J'ai lu et j'accepte les conditions de ce contrat de bail.
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success">Signer le contrat</button>
                </form>
            <?php elseif ($contrat['statut'] === 'actif') : ?>
                <p class="text-success mb-0">✅ Contrat signé par les deux parties. Il est actif.</p>
            <?php else : ?>
                <p class="text-muted">Contrat déjà signé de votre côté.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (! empty($avenants)) : ?>
        <h5 class="mt-4">Avenants</h5>
        <?php foreach ($avenants as $a) : ?>
            <div class="card shadow-sm mt-2">
                <div class="card-body">
                    <p class="mb-1"><strong>Avenant n°<?= $a['numero_avenant'] ?></strong> — <?= esc(str_replace('_', ' ', $a['type_avenant'])) ?></p>
                    <p class="mb-1 small text-muted">
                        <?= esc($a['champ_modifie']) ?> :
                        <?= esc($a['ancienne_valeur'] ?? '—') ?> → <?= esc($a['nouvelle_valeur']) ?>
                    </p>
                    <p class="mb-2 small">Effet à partir du <?= date('d/m/Y', strtotime($a['date_effet'])) ?></p>

                    <?php if ($a['statut'] === 'signe_bailleur') : ?>
                        <form method="post" action="<?= site_url('client/avenant/' . $a['id_avenant'] . '/signer') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-success">Signer cet avenant</button>
                        </form>
                    <?php elseif ($a['statut'] === 'actif') : ?>
                        <span class="badge bg-success">Actif</span>
                    <?php else : ?>
                        <span class="badge bg-secondary"><?= esc(ucfirst($a['statut'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>