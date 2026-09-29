<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un avenant — Contrat <?= esc($contrat['numero_contrat'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?= view('partials/navigation_proprietaire') ?>

<div class="container py-4 page-narrow">
    <?php if (session('erreur')): ?><div class="alert alert-danger"><?= esc(session('erreur')) ?></div><?php endif; ?>

    <div class="mb-4">
        <h1 class="h4 mb-1">Créer un avenant</h1>
        <p class="text-muted mb-0">
            Contrat <?= esc($contrat['numero_contrat'] ?? '') ?> — <?= esc($contrat['maison_titre'] ?? '') ?>
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= site_url('proprietaire/contrats/' . $contrat['id_contrat'] . '/avenants') ?>">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="type_avenant">Type d'avenant</label>
                        <input id="type_avenant" name="type_avenant" class="form-control"
                               value="<?= esc(old('type_avenant', 'modification')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="date_effet">Date d'effet</label>
                        <input id="date_effet" type="date" name="date_effet" class="form-control"
                               value="<?= esc(old('date_effet', date('Y-m-d'))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="justification">Justification</label>
                        <input id="justification" name="justification" class="form-control"
                               value="<?= esc(old('justification', '')) ?>" placeholder="Motif">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="champ_modifie">Article(s) / champ(s) modifié(s)</label>
                    <textarea id="champ_modifie" name="champ_modifie" class="form-control" rows="3"
                              placeholder="Article 3 - Loyer&#10;Article 5 - Durée" required><?= esc(old('champ_modifie', '')) ?></textarea>
                    <div class="form-text">Une ligne par article modifié.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="ancienne_valeur">Ancienne valeur</label>
                        <textarea id="ancienne_valeur" name="ancienne_valeur" class="form-control" rows="3"
                                  placeholder="Valeur actuelle"><?= esc(old('ancienne_valeur', $contrat['loyer_mensuel'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nouvelle_valeur">Nouvelle valeur</label>
                        <textarea id="nouvelle_valeur" name="nouvelle_valeur" class="form-control" rows="3"
                                  placeholder="Valeur modifiée" required><?= esc(old('nouvelle_valeur', '')) ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Proposer l'avenant</button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('proprietaire/contrats/' . $contrat['id_contrat']) ?>">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>