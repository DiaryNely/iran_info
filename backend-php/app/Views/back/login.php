<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Connexion', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
  <div class="login-wrap">
    <section class="login-card">
      <h1>Connexion backoffice</h1>

      <?php if (!empty($error ?? '')): ?>
        <div class="toast-inline toast-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post" action="/backoffice/login" class="form-grid">
        <label>
          Email
          <input type="email" name="email" required autocomplete="email" value="admin@iran.local">
        </label>

        <label>
          Mot de passe
          <input type="password" name="password" required autocomplete="current-password" value="admin123">
        </label>

        <div class="actions">
          <button class="btn btn-primary" type="submit">Se connecter</button>
          <a class="btn btn-secondary" href="/">Retour au frontoffice</a>
        </div>
      </form>
    </section>
  </div>
</body>
</html>
