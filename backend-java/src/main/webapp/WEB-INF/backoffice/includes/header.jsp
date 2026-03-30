<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<%-- Admin layout header - included by all admin pages --%>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - ${param.pageTitle} | Iran Info</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="${pageContext.request.contextPath}/assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <h1>Iran Info</h1>
        <p class="sidebar-sub">BackOffice Admin</p>
        <nav>
            <a href="${pageContext.request.contextPath}/backoffice/dashboard"
               class="${param.activePage == 'dashboard' ? 'active' : ''}">Dashboard</a>
            <a href="${pageContext.request.contextPath}/backoffice/articles"
               class="${param.activePage == 'articles' ? 'active' : ''}">Articles</a>
            <a href="${pageContext.request.contextPath}/backoffice/categories"
               class="${param.activePage == 'categories' ? 'active' : ''}">Catégories</a>
        </nav>
        <div class="sidebar-footer">
            <p>${sessionScope.username != null ? sessionScope.username : 'admin'}</p>
            <a href="${pageContext.request.contextPath}/backoffice/logout" class="btn btn-outline">Logout</a>
        </div>
    </aside>
    <main class="content">
