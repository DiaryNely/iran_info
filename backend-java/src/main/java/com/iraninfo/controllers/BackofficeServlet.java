package com.iraninfo.controllers;

import com.iraninfo.services.ArticleService;
import com.iraninfo.services.CategoryService;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;

@WebServlet("/backoffice/*")
public class BackofficeServlet extends HttpServlet {

    private static final String DEFAULT_ADMIN_USERNAME = "admin";
    private static final String DEFAULT_ADMIN_EMAIL = "admin@iran.local";
    private static final String DEFAULT_ADMIN_PASSWORD = "admin123";

    private final ArticleService articleService = new ArticleService();
    private final CategoryService categoryService = new CategoryService();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp)
            throws ServletException, IOException {
        String pathInfo = req.getPathInfo();
        if (pathInfo == null)
            pathInfo = "/";

        try {
            switch (pathInfo) {
                case "/":
                case "/login":
                    req.getRequestDispatcher("/WEB-INF/backoffice/login.jsp").forward(req, resp);
                    break;

                case "/dashboard":
                    // Check auth via session
                    if (!isAuthenticated(req)) {
                        resp.sendRedirect(req.getContextPath() + "/backoffice/login");
                        return;
                    }
                    try {
                        var articles = articleService.findAllForAdmin();
                        var categories = categoryService.findAll();
                        req.setAttribute("articles", articles);
                        req.setAttribute("categories", categories);
                        req.setAttribute("totalArticles", articles.size());
                        req.setAttribute("totalCategories", categories.size());
                        long published = articles.stream().filter(a -> "published".equals(a.getStatus())).count();
                        long featured = articles.stream().filter(a -> a.isFeatured()).count();
                        req.setAttribute("totalPublished", published);
                        req.setAttribute("totalFeatured", featured);
                    } catch (Exception e) {
                        req.setAttribute("error", e.getMessage());
                    }
                    req.getRequestDispatcher("/WEB-INF/backoffice/dashboard.jsp").forward(req, resp);
                    break;

                case "/articles":
                    if (!isAuthenticated(req)) {
                        resp.sendRedirect(req.getContextPath() + "/backoffice/login");
                        return;
                    }
                    try {
                        req.setAttribute("articles", articleService.findAllForAdmin());
                        req.setAttribute("categories", categoryService.findAll());
                    } catch (Exception e) {
                        req.setAttribute("error", e.getMessage());
                    }
                    req.getRequestDispatcher("/WEB-INF/backoffice/articles.jsp").forward(req, resp);
                    break;

                case "/categories":
                    if (!isAuthenticated(req)) {
                        resp.sendRedirect(req.getContextPath() + "/backoffice/login");
                        return;
                    }
                    try {
                        req.setAttribute("categories", categoryService.findAll());
                    } catch (Exception e) {
                        req.setAttribute("error", e.getMessage());
                    }
                    req.getRequestDispatcher("/WEB-INF/backoffice/categories.jsp").forward(req, resp);
                    break;

                case "/logout":
                    req.getSession().invalidate();
                    resp.sendRedirect(req.getContextPath() + "/backoffice/login");
                    break;

                default:
                    resp.sendError(404);
                    break;
            }
        } catch (Exception e) {
            resp.sendError(500, "Internal server error");
        }
    }

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp)
            throws ServletException, IOException {
        String pathInfo = req.getPathInfo();
        if (pathInfo == null)
            pathInfo = "/";

        switch (pathInfo) {
            case "/login":
                handleLogin(req, resp);
                break;
            default:
                resp.sendError(404);
                break;
        }
    }

    private void handleLogin(HttpServletRequest req, HttpServletResponse resp)
            throws ServletException, IOException {
        String email = req.getParameter("email");
        String password = req.getParameter("password");

        try {
            var authService = new com.iraninfo.services.AuthService();

            // Keep parity with the old React backoffice: first login with default
            // credentials auto-provisions the admin account if it doesn't exist yet.
            if (DEFAULT_ADMIN_EMAIL.equalsIgnoreCase(email)
                    && DEFAULT_ADMIN_PASSWORD.equals(password)) {
                try {
                    authService.register(DEFAULT_ADMIN_USERNAME, DEFAULT_ADMIN_EMAIL, DEFAULT_ADMIN_PASSWORD);
                } catch (IllegalArgumentException ignored) {
                    // User already exists, continue with normal login.
                }
            }

            var result = authService.login(email, password);

            // Store in session
            @SuppressWarnings("unchecked")
            var userMap = (java.util.Map<String, Object>) result.get("user");
            req.getSession().setAttribute("accessToken", result.get("accessToken"));
            req.getSession().setAttribute("user", userMap);
            req.getSession().setAttribute("username", userMap.get("username"));

            resp.sendRedirect(req.getContextPath() + "/backoffice/dashboard");
        } catch (SecurityException e) {
            req.setAttribute("error", "Identifiants invalides.");
            req.getRequestDispatcher("/WEB-INF/backoffice/login.jsp").forward(req, resp);
        } catch (Exception e) {
            req.setAttribute("error", e.getMessage());
            req.getRequestDispatcher("/WEB-INF/backoffice/login.jsp").forward(req, resp);
        }
    }

    private boolean isAuthenticated(HttpServletRequest req) {
        return req.getSession(false) != null
                && req.getSession().getAttribute("accessToken") != null;
    }
}
