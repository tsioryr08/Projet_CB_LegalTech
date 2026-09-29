<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avenant n°<?= esc($avenant['numero_avenant'] ?? '') ?> — Contrat <?= esc($document['numero_contrat'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6f8; }
        .paper {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            line-height: 1.8;
        }
        .paper .title-line {
            text-align: center;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .paper .section-title {
            font-weight: 700;
            margin: 1.1rem 0 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid #e9ecef;
        }
        .article-box {
            border: 1px solid #e9ecef;
            border-radius: 0.4rem;
            padding: 0.85rem 1rem;
            margin-bottom: 0.75rem;
        }
        .article-box:last-child { margin-bottom: 0; }
        .article-box .article-title { font-weight: 700; margin-bottom: 0.3rem; }
        .signature-box {
            border: 1px solid #dee2e6;
            border-radius: 0.4rem;
            padding: 1rem;
            height: 100%;
        }
        .signature-box .role { font-size: 0.8rem; text-transform: uppercase; color: #6c757d; }
        .signature-box .name { font-weight: 600; }
        .signature-box .hint { font-size: 0.85rem; color: #6c757d; margin-top: 0.4rem; }
        .footer-actions { display: flex; justify-content: flex-end; gap: 0.75rem; }
        @media print {
            .toolbar, .footer-actions, nav { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container py-4" style="max-width: 1000px;">
    <?php
    // Libellés courts pour les badges de statut
    $libellesStatut = [
        'propose'        => 'Proposé',
        'signe_bailleur' => 'Signé par le bailleur',
        'valide'         => 'Actif',
        'actif'          => 'Actif',
        'refuse'         => 'Refusé',
        'annule'         => 'Annulé',
    ];
    $classesStatut = [
        'propose'        => 'bg-secondary',
        'signe_bailleur' => 'bg-warning text-dark',
        'valide'         => 'bg-success',
        'actif'          => 'bg-success',
        'refuse'         => 'bg-danger',
        'annule'         => 'bg-dark',
    ];
    $statutAvenant = $document['statut'] ?? ($avenant['statut'] ?? 'propose');
    $statutContrat = $contrat['statut'] ?? 'genere';
    ?>

    <?php if (session('succes')): ?><div class="alert alert-success"><?= esc(session('succes')) ?></div><?php endif; ?>
    <?php if (session('erreur')): ?><div class="alert alert-danger"><?= esc(session('erreur')) ?></div><?php endif; ?>

    <!-- En-tête simple -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Avenant n°<?= esc($avenant['numero_avenant'] ?? '') ?></h1>
            <p class="text-muted mb-0">
                Contrat <?= esc($document['numero_contrat'] ?? '') ?> — Date d'effet <?= esc($document['date_effet'] ?? '') ?>
            </p>
        </div>
        <span class="badge <?= $classesStatut[$statutAvenant] ?? 'bg-secondary' ?> fs-6">
            <?= esc($libellesStatut[$statutAvenant] ?? ucfirst($statutAvenant)) ?>
        </span>
    </div>

    <!-- Boutons principaux, bien visibles -->
    <div class="d-flex gap-2 mb-4">
        <a href="<?= site_url('avenants/' . ($avenant['id_avenant'] ?? 0) . '/pdf') ?>" class="btn btn-primary">
            Télécharger le PDF
        </a>
        <a href="<?= site_url((session('role') === 'client' ? 'client/contrats/' : 'proprietaire/contrats/') . ($contrat['id_contrat'] ?? 0)) ?>"
           class="btn btn-outline-secondary">
            Retour au contrat
        </a>
    </div>

    <!-- Infos essentielles -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Bailleur</div>
                    <div class="fw-semibold"><?= esc($document['nom_bailleur'] ?? '') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Locataire</div>
                    <div class="fw-semibold"><?= esc($document['nom_locataire'] ?? '') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Logement</div>
                    <div class="fw-semibold"><?= esc($document['adresse_bien'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contrat initial -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Contrat de bail initial</strong>
            <span class="text-muted small">Non modifié</span>
        </div>
        <div class="card-body">
            <div class="paper mb-3">
                <?= nl2br(esc($document['contrat_initial_texte'] ?? '')) ?>
            </div>
            <div class="row g-3">
                <?php if (! empty($document['contrat_signatures'])): ?>
                    <?php foreach ($document['contrat_signatures'] as $signature): ?>
                        <div class="col-md-6">
                            <div class="signature-box">
                                <div class="role"><?= esc($signature['role_signataire']) ?></div>
                                <div class="name"><?= esc($signature['nom_affiche']) ?></div>
                                <div class="hint">Signé le <?= esc($signature['signe_le']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">Aucune signature enregistrée pour le contrat initial.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Avenant -->
    <div class="card mb-4">
        <div class="card-header">
            <strong>Avenant au contrat de bail</strong>
        </div>
        <div class="card-body">
            <div class="paper mb-3">
                <div class="title-line">Avenant au contrat de bail</div>

                <div class="section-title">Entre les soussignés</div>
                <p>Le bailleur : <?= esc($document['nom_bailleur'] ?? '') ?>, demeurant <?= esc($document['adresse_bailleur'] ?? '') ?></p>
                <p>Le(s) locataire(s) : <?= esc($document['nom_locataire'] ?? '') ?>, demeurant <?= esc($document['adresse_locataire'] ?? '') ?></p>

                <div class="section-title">Article 1 — Objet de l'avenant</div>
                <p>Le présent avenant modifie le contrat de bail conclu le <?= esc($document['date_contrat_initial'] ?? '') ?> pour le logement situé <?= esc($document['adresse_bien'] ?? '') ?>.</p>

                <div class="section-title">Article 2 — Modification(s) apportée(s)</div>
                <?php foreach (($document['modifications'] ?? []) as $modification): ?>
                    <div class="article-box">
                        <div class="article-title"><?= esc($modification['article'] ?? '') ?></div>
                        <div>Ancienne valeur : <?= esc($modification['ancienne'] ?? '') ?></div>
                        <div>Nouvelle valeur : <?= esc($modification['nouvelle'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
                <p class="mb-0">Le reste du contrat demeure sans changement.</p>

                <div class="section-title">Article 3 — Date d'effet</div>
                <p>Le présent avenant prend effet le <?= esc($document['date_effet'] ?? '') ?>.</p>

                <div class="section-title">Article 4 — Maintien des autres clauses</div>
                <p class="mb-0">Toutes les autres clauses et conditions du bail d'origine demeurent inchangées.</p>
            </div>

            <?php
            $signeBailleur  = in_array($statutAvenant, ['signe_bailleur', 'valide', 'actif'], true);
            $signeLocataire = in_array($statutAvenant, ['valide', 'actif'], true);
            ?>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="signature-box">
                        <div class="role">Bailleur</div>
                        <div class="name"><?= esc($document['nom_bailleur'] ?? '') ?></div>
                        <div class="hint"><?= $signeBailleur ? 'Signé' : 'En attente de signature' ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="signature-box">
                        <div class="role">Locataire</div>
                        <div class="name"><?= esc($document['nom_locataire'] ?? '') ?></div>
                        <div class="hint"><?= $signeLocataire ? 'Signé' : 'En attente de signature' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="footer-actions mb-2">
        <?php if ($statutAvenant === 'propose' && session('role') === 'proprietaire'): ?>
            <form method="post" action="<?= site_url('proprietaire/avenants/' . $avenant['id_avenant'] . '/signer') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-lg">Signer l'avenant</button>
            </form>
        <?php elseif ($statutAvenant === 'signe_bailleur' && session('role') === 'client'): ?>
            <form method="post" action="<?= site_url('client/avenants/' . $avenant['id_avenant'] . '/signer') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-lg">Signer l'avenant</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>