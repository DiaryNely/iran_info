package com.iraninfo.controllers;

import com.iraninfo.services.CategoryService;
import com.iraninfo.utils.JsonUtil;
import com.iraninfo.utils.RequestUtil;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.Map;

@WebServlet("/api/categories/*")
public class CategoryServlet extends HttpServlet {

    private final CategoryService categoryService = new CategoryService();

    @Override
    protected void service(HttpServletRequest req, HttpServletResponse resp) throws IOException {
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
        if (pathParam == null) {
            // GET /api/categories
            ResponseUtil.sendJson(resp, 200, categoryService.findAll());
        } else {
            ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
        }
    }

    private void handlePost(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String pathParam = RequestUtil.getPathParam(req);
        if (pathParam != null) {
            ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
            return;
        }

        String body = RequestUtil.readBody(req);
        Map<String, Object> data = JsonUtil.getMapper().readValue(body, Map.class);

        String name = getString(data, "name");
        String slug = getString(data, "slug");
        String description = getString(data, "description");
        String metaTitle = getString(data, "metaTitle");
        String metaDescription = getString(data, "metaDescription");

        if (name == null || name.length() < 2) {
            throw new IllegalArgumentException("Name is required (min 2 chars)");
        }
        if (slug == null || slug.length() < 2) {
            throw new IllegalArgumentException("Slug is required (min 2 chars)");
        }

        var created = categoryService.create(name, slug, description, metaTitle, metaDescription);
        ResponseUtil.sendJson(resp, 201, created);
    }

    private void handlePatch(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        long id = RequestUtil.parseIdFromPath(req);
        if (id <= 0) {
            ResponseUtil.sendError(resp, 400, "Invalid category ID", req.getRequestURI());
            return;
        }

        String body = RequestUtil.readBody(req);
        Map<String, Object> data = JsonUtil.getMapper().readValue(body, Map.class);

        String name = getString(data, "name");
        String slug = getString(data, "slug");
        String description = getString(data, "description");
        String metaTitle = getString(data, "metaTitle");
        String metaDescription = getString(data, "metaDescription");

        var updated = categoryService.update(id, name, slug, description, metaTitle, metaDescription);
        ResponseUtil.sendJson(resp, 200, updated);
    }

    private void handleDelete(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        long id = RequestUtil.parseIdFromPath(req);
        if (id <= 0) {
            ResponseUtil.sendError(resp, 400, "Invalid category ID", req.getRequestURI());
            return;
        }

        var result = categoryService.remove(id);
        ResponseUtil.sendJson(resp, 200, result);
    }

    private String getString(Map<String, Object> data, String key) {
        Object val = data.get(key);
        return val != null ? val.toString() : null;
    }
}
