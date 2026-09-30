<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat <?= esc($numero_contrat) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_proprietaire') ?>

<div class="container py-4">
    <?php if (session('succes')): ?><div class="alert alert-success"><?= esc(session('succes')) ?></div><?php endif; ?>
    <?php if (session('erreur')): ?><div class="alert alert-danger"><?= esc(session('erreur')) ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-0">Contrat <?= esc($numero_contrat) ?></h2>
            <p class="text-muted mb-0"><?= esc($maison_titre ?? '') ?> | Statut : <?= esc($statut) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= site_url('proprietaire/avenants') ?>">Mes avenants</a>
            <a class="btn btn-outline-secondary" href="<?= site_url('contrats/' . $id_contrat . '/pdf') ?>">Télécharger le PDF</a>
            <!-- <a class="btn btn-outline-secondary" href="<?= site_url('contrats/' . $id_contrat . '/fiche-fiscale') ?>">Télécharger la fiche fiscale</a> -->
            <a class="btn btn-primary" href="<?= site_url('proprietaire/contrats/' . $id_contrat . '/avenants/ajouter') ?>">Créer un avenant</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card card-body">Locataire<br><strong><?= esc(trim(($client_prenoms ?? '') . ' ' . ($client_nom ?? ''))) ?></strong></div></div>
        <div class="col-md-4"><div class="card card-body">Propriétaire<br><strong><?= esc(trim(($proprietaire_prenoms ?? '') . ' ' . ($proprietaire_nom ?? ''))) ?></strong></div></div>
        <div class="col-md-4"><div class="card card-body">Usage<br><strong><?= esc($type_code ?? '') ?></strong></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5>Contrat de bail</h5>
            <div class="contract-document">
                <?= nl2br(esc($texte_contrat ?? '')) ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5>Résumé fiscal</h5>
            <ul class="mb-0">
                <li>Loyer mensuel : <?= number_format((float) $loyer_mensuel, 0, ',', ' ') ?> Ar</li>
                <li>Droit d'enregistrement : <?= number_format((float) $montant_droit_enregistrement, 0, ',', ' ') ?> Ar</li>
                <li>Taux appliqué : <?= esc($taux_enregistrement_applique) ?>%</li>
                <li>Fiche fiscale : <?= esc($statut_enregistrement ?? 'non disponible') ?></li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5>Signatures</h5>
            <ul class="mb-0">
                <?php foreach (($signatures ?? []) as $signature): ?>
                    <li><?= esc($signature['role_signataire']) ?> - <?= esc($signature['nom_affiche']) ?> - <?= esc($signature['signe_le']) ?></li>
                <?php endforeach; ?>
                <?php if (empty($signatures)): ?>
                    <li class="text-muted">Aucune signature enregistrée.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            
            <h5>Avenants en attente</h5>
            <?php if (! empty($avenants)): ?>
                <div class="list-group">
                    <?php foreach ($avenants as $avenant): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Avenant #<?= esc($avenant['numero_avenant']) ?></strong> - <?= esc($avenant['type_avenant']) ?><br>
                                    <small class="text-muted"><?= esc($avenant['champ_modifie']) ?> : <?= esc($avenant['ancienne_valeur'] ?? '—') ?> → <?= esc($avenant['nouvelle_valeur']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary mb-2"><?= esc($avenant['statut']) ?></span>
                                    <div class="mt-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('avenants/' . $avenant['id_avenant']) ?>">Consulter</a>
                                    </div>
                                    <?php if (session('role') === 'proprietaire' && $avenant['statut'] === 'propose'): ?>
                                        <form method="post" action="<?= site_url('proprietaire/avenants/' . $avenant['id_avenant'] . '/signer') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-primary">Signer</button>
                                        </form>
                                    <?php elseif (session('role') === 'client' && $avenant['statut'] === 'signe_bailleur'): ?>
                                        <form method="post" action="<?= site_url('client/avenants/' . $avenant['id_avenant'] . '/signer') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-primary">Signer</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-muted">Aucun avenant en attente.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <?php if ($statut === 'genere'): ?>
            <form method="post" action="<?= site_url('contrats/' . $id_contrat . '/signer-bailleur') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">J'ai lu et j'accepte (bailleur)</button>
            </form>
        <?php endif; ?>
        <a href="<?= site_url('proprietaire/contrats') ?>" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
</body>
</html>
