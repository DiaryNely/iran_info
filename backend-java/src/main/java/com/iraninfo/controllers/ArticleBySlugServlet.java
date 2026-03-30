package com.iraninfo.controllers;

import com.iraninfo.services.ArticleService;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;

@WebServlet("/api/article/*")
public class ArticleBySlugServlet extends HttpServlet {

    private final ArticleService articleService = new ArticleService();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        String pathInfo = req.getPathInfo();
        if (pathInfo == null || pathInfo.equals("/")) {
            ResponseUtil.sendError(resp, 400, "Slug is required", req.getRequestURI());
            return;
        }

        String slug = pathInfo.substring(1);

        try {
            var article = articleService.findOneBySlug(slug);
            ResponseUtil.sendJson(resp, 200, article);
        } catch (IllegalStateException e) {
            ResponseUtil.sendError(resp, 404, e.getMessage(), req.getRequestURI());
        } catch (Exception e) {
            ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
        }
    }
}
