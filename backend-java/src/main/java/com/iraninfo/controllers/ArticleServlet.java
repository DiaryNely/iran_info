package com.iraninfo.controllers;

import com.iraninfo.services.ArticleService;
import com.iraninfo.utils.RequestUtil;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.MultipartConfig;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.Part;
import java.io.IOException;
import java.util.*;

@WebServlet("/api/articles/*")
@MultipartConfig(maxFileSize = 5 * 1024 * 1024, // 5 MB per file
        maxRequestSize = 50 * 1024 * 1024, // 50 MB total
        fileSizeThreshold = 1024 * 1024 // 1 MB before writing to disk
)
public class ArticleServlet extends HttpServlet {

    private final ArticleService articleService = new ArticleService();

    @Override
    protected void service(HttpServletRequest req, HttpServletResponse resp)
            throws IOException, ServletException {
        String method = req.getMethod().toUpperCase();

        try {
            switch (method) {
                case "GET" -> handleGet(req, resp);
                case "POST" -> handlePost(req, resp);
                case "PATCH" -> handlePatch(req, resp);
                case "DELETE" -> handleDelete(req, resp);
                case "OPTIONS" -> resp.setStatus(200);
                default -> ResponseUtil.sendError(resp, 405, "Method not allowed", req.getRequestURI());
            }
        } catch (IllegalArgumentException e) {
            ResponseUtil.sendError(resp, 400, e.getMessage(), req.getRequestURI());
        } catch (IllegalStateException e) {
            ResponseUtil.sendError(resp, 404, e.getMessage(), req.getRequestURI());
        } catch (Exception e) {
            ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
        }
    }

    private void handleGet(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String pathParam = RequestUtil.getPathParam(req);
        if (pathParam != null) {
            ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
            return;
        }
        // GET /api/articles → list published
        var articles = articleService.findAllPublished();
        ResponseUtil.sendJson(resp, 200, articles);
    }

    private void handlePost(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String pathParam = RequestUtil.getPathParam(req);
        if (pathParam != null) {
            ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
            return;
        }

        long userId = (long) req.getAttribute("userId");

        Map<String, String> fields = extractFormFields(req);
        Part coverImage = getPartSafe(req, "coverImage");
        List<Part> galleryImages = getPartsSafe(req, "galleryImages");

        var article = articleService.create(fields, userId, coverImage, galleryImages);
        ResponseUtil.sendJson(resp, 201, article);
    }

    private void handlePatch(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        long id = RequestUtil.parseIdFromPath(req);
        if (id <= 0) {
            ResponseUtil.sendError(resp, 400, "Invalid article ID", req.getRequestURI());
            return;
        }

        Map<String, String> fields = extractFormFields(req);
        Part coverImage = getPartSafe(req, "coverImage");
        List<Part> galleryImages = getPartsSafe(req, "galleryImages");

        var article = articleService.update(id, fields, coverImage, galleryImages);
        ResponseUtil.sendJson(resp, 200, article);
    }

    private void handleDelete(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        long id = RequestUtil.parseIdFromPath(req);
        if (id <= 0) {
            ResponseUtil.sendError(resp, 400, "Invalid article ID", req.getRequestURI());
            return;
        }

        var result = articleService.remove(id);
        ResponseUtil.sendJson(resp, 200, result);
    }

    // ─── Helpers ──────────────────────────────────────────────

    private Map<String, String> extractFormFields(HttpServletRequest req) throws IOException, ServletException {
        Map<String, String> fields = new LinkedHashMap<>();
        Collection<Part> parts = req.getParts();
        for (Part part : parts) {
            String name = part.getName();
            // Skip file parts
            if (part.getSubmittedFileName() != null)
                continue;
            // Read text field
            String value = new String(part.getInputStream().readAllBytes(), "UTF-8");
            // Handle multiple values with same name (e.g. galleryAlts, categoryIds)
            if (fields.containsKey(name)) {
                // Append as JSON array-like
                String existing = fields.get(name);
                if (!existing.startsWith("[")) {
                    existing = "[\"" + existing + "\"";
                } else {
                    existing = existing.substring(0, existing.length() - 1);
                }
                fields.put(name, existing + ",\"" + value + "\"]");
            } else {
                fields.put(name, value);
            }
        }
        return fields;
    }

    private Part getPartSafe(HttpServletRequest req, String name) {
        try {
            Part part = req.getPart(name);
            if (part != null && part.getSubmittedFileName() != null && part.getSize() > 0) {
                return part;
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    private List<Part> getPartsSafe(HttpServletRequest req, String name) {
        List<Part> result = new ArrayList<>();
        try {
            for (Part part : req.getParts()) {
                if (name.equals(part.getName()) && part.getSubmittedFileName() != null && part.getSize() > 0) {
                    result.add(part);
                }
            }
        } catch (Exception ignored) {
        }
        return result;
    }
}
