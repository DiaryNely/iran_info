<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Iran Info | Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="${pageContext.request.contextPath}/assets/admin.css">
</head>
<body>
<section class="login-wrap">
    <div class="login-card">
        <h1>Admin Panel</h1>
        <p>Connecte-toi pour gérer les contenus.</p>

        <c:if test="${not empty error}">
            <div class="toast-inline toast-error">${error}</div>
        </c:if>

        <form method="post" action="${pageContext.request.contextPath}/backoffice/login" class="form-grid">
            <label>
                Email
                <input type="email" name="email" value="admin@iran.local" required />
            </label>

            <label>
                Mot de passe
                <input type="password" name="password" value="admin123" required />
            </label>

            <button class="btn btn-primary" type="submit">Login</button>
        </form>

        <small>Compte par défaut: admin@iran.local / admin123</small>
    </div>
</section>
</body>
</html>
