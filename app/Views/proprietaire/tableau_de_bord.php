<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord — Espace Propriétaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">LegalTech Bail — Espace Propriétaire</span>
        <a href="<?= site_url('auth/deconnexion') ?>" class="btn btn-outline-light btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <h2>Bienvenue, <?= esc($nom) ?> 👋</h2>
    <p class="text-muted">La gestion de vos biens et des demandes reçues s'affichera ici.</p>

    <div class="alert alert-info">
        Connexion réussie. Prochaine étape : brancher <code>MaisonModel</code> et <code>DemandeModel</code>.
    </div>
</div>
</body>
</html>