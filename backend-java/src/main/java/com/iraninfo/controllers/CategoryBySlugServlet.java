package com.iraninfo.controllers;

import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

import com.iraninfo.services.CategoryService;
import com.iraninfo.utils.ResponseUtil;

import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

@WebServlet(urlPatterns = { "/api/categorie/*", "/categorie/*" })
public class CategoryBySlugServlet extends HttpServlet {

    private final CategoryService categoryService = new CategoryService();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        // Legacy URL support: /categorie?id=5 -> 308 /categorie/politique
        if ("/categorie".equals(req.getServletPath()) && (req.getPathInfo() == null || "/".equals(req.getPathInfo()))) {
            String idParam = req.getParameter("id");
            if (idParam != null && !idParam.isBlank()) {
                try {
                    long id = Long.parseLong(idParam);
                    var category = categoryService.findOneById(id);
                    String encodedSlug = URLEncoder.encode(category.getSlug(), StandardCharsets.UTF_8);
                    String location = req.getContextPath() + "/categorie/" + encodedSlug;
                    resp.setStatus(308);
                    resp.setHeader("Location", location);
                    return;
                } catch (NumberFormatException e) {
                    ResponseUtil.sendError(resp, 400, "Invalid category id", req.getRequestURI());
                    return;
                } catch (IllegalStateException e) {
                    ResponseUtil.sendError(resp, 404, e.getMessage(), req.getRequestURI());
                    return;
                } catch (Exception e) {
                    ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
                    return;
                }
            }
        }

        String pathInfo = req.getPathInfo();
        if (pathInfo == null || pathInfo.equals("/")) {
            ResponseUtil.sendError(resp, 400, "Slug is required", req.getRequestURI());
            return;
        }

        String slug = pathInfo.substring(1);

        try {
            var category = categoryService.findOneBySlug(slug);
            ResponseUtil.sendJson(resp, 200, category);
        } catch (IllegalStateException e) {
            ResponseUtil.sendError(resp, 404, e.getMessage(), req.getRequestURI());
        } catch (Exception e) {
            ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
        }
    }
}
