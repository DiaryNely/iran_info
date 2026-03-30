package com.iraninfo.controllers;

import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Set;

import com.iraninfo.services.ArticleService;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

@WebServlet(urlPatterns = { "/frontoffice/*", "/article/*" })
public class FrontofficePageServlet extends HttpServlet {

    private static final Set<String> ALLOWED_PAGES = Set.of(
            "HomeFrontPage.jsp",
            "ArticleFrontPage.jsp",
            "PublicLayout.jsp");

    private final ArticleService articleService = new ArticleService();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        if ("/article".equals(req.getServletPath())) {
            handleCleanArticleRoute(req, resp);
            return;
        }

        String pathInfo = req.getPathInfo();

        if (pathInfo == null || "/".equals(pathInfo)) {
            resp.setStatus(308);
            resp.setHeader("Location", req.getContextPath() + "/");
            return;
        }

        if ("/HomeFrontPage.jsp".equals(pathInfo)) {
            resp.setStatus(308);
            resp.setHeader("Location", req.getContextPath() + "/");
            return;
        }

        if ("/ArticleFrontPage.jsp".equals(pathInfo)) {
            String slug = req.getParameter("slug");
            if (slug != null && !slug.isBlank()) {
                resp.setStatus(308);
                resp.setHeader("Location", req.getContextPath() + "/article/" + slug);
                return;
            }
            resp.sendError(HttpServletResponse.SC_BAD_REQUEST, "Slug is required");
            return;
        }

        String page = pathInfo.startsWith("/") ? pathInfo.substring(1) : pathInfo;
        if (!ALLOWED_PAGES.contains(page)) {
            resp.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }

        req.getRequestDispatcher("/WEB-INF/frontoffice/" + page).forward(req, resp);
    }

    private void handleCleanArticleRoute(HttpServletRequest req, HttpServletResponse resp)
            throws ServletException, IOException {
        String pathInfo = req.getPathInfo();

        if (pathInfo == null || "/".equals(pathInfo)) {
            // Legacy support: /article?id=12 -> /article/slug
            String idParam = req.getParameter("id");
            if (idParam != null && !idParam.isBlank()) {
                try {
                    long id = Long.parseLong(idParam);
                    var article = articleService.findOneById(id);
                    String encodedSlug = URLEncoder.encode(article.getSlug(), StandardCharsets.UTF_8);
                    resp.setStatus(308);
                    resp.setHeader("Location", req.getContextPath() + "/article/" + encodedSlug);
                    return;
                } catch (NumberFormatException e) {
                    resp.sendError(HttpServletResponse.SC_BAD_REQUEST, "Invalid article id");
                    return;
                } catch (IllegalStateException e) {
                    resp.sendError(HttpServletResponse.SC_NOT_FOUND, e.getMessage());
                    return;
                } catch (Exception e) {
                    resp.sendError(HttpServletResponse.SC_INTERNAL_SERVER_ERROR, "Internal server error");
                    return;
                }
            }

            resp.sendError(HttpServletResponse.SC_BAD_REQUEST, "Slug is required");
            return;
        }

        String slug = pathInfo.startsWith("/") ? pathInfo.substring(1) : pathInfo;
        String encodedSlug = URLEncoder.encode(slug, StandardCharsets.UTF_8);
        req.getRequestDispatcher("/WEB-INF/frontoffice/ArticleFrontPage.jsp?slug=" + encodedSlug).forward(req, resp);
    }
}
