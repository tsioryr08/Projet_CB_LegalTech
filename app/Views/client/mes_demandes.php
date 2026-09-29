<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes demandes — Espace Locataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?= view('partials/navigation_client') ?>

<div class="container pb-5">
    <h2 class="mb-4">Mes demandes</h2>

    <?php if (session('succes')) : ?>
        <div class="alert alert-success"><?= esc(session('succes')) ?></div>
    <?php endif; ?>
    <?php if (session('erreur')) : ?>
        <div class="alert alert-danger"><?= esc(session('erreur')) ?></div>
    <?php endif; ?>

    <?php if (empty($demandes)) : ?>
        <div class="alert alert-info">Vous n'avez encore fait aucune demande de location.</div>
    <?php else : ?>
        <?php
        $badges = [
            'envoyee' => 'bg-warning text-dark',
            'validee' => 'bg-success',
            'refusee' => 'bg-danger',
            'annulee' => 'bg-secondary',
        ];
        $libelles = [
            'envoyee' => 'En attente',
            'validee' => 'Validée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée',
        ];
        ?>
        <div class="table-responsive">
            <table class="table table-bordered bg-white align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Maison</th>
                        <th>Ville</th>
                        <th>Loyer</th>
                        <th>Statut</th>
                        <th>Contrat</th>
                        <th>Date de la demande</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($demandes as $d) : ?>
                    <tr>
                        <td><?= esc($d['titre']) ?></td>
                        <td><?= esc($d['nom_ville']) ?></td>
                        <td><?= number_format($d['loyer_mensuel'], 0, ',', ' ') ?> Ar</td>
                        <td>
                            <span class="badge <?= $badges[$d['statut']] ?? 'bg-secondary' ?>">
                                <?= $libelles[$d['statut']] ?? esc($d['statut']) ?>
                            </span>
                            <?php if ($d['statut'] === 'refusee' && $d['motif_refus']) : ?>
                                <div class="small text-muted">Motif : <?= esc($d['motif_refus']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($d['contrat'])) : ?>
                                <a href="<?= site_url('client/contrats/' . $d['contrat']['id_contrat']) ?>" class="btn btn-sm btn-primary">Mon contrat</a>
                            <?php else : ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y à H:i', strtotime($d['date_demande'])) ?></td>
                        <td>
                            <?php if ($d['statut'] === 'validee') : ?>
                                <a href="<?= site_url('client/dossier/' . $d['id_demande']) ?>" class="btn btn-sm btn-primary">
                                    Remplir le dossier
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>