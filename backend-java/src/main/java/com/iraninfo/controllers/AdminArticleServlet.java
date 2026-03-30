package com.iraninfo.controllers;

import com.iraninfo.services.ArticleService;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;

@WebServlet("/api/admin/articles")
public class AdminArticleServlet extends HttpServlet {

    private final ArticleService articleService = new ArticleService();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        try {
            var articles = articleService.findAllForAdmin();
            ResponseUtil.sendJson(resp, 200, articles);
        } catch (Exception e) {
            ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
        }
    }
}
