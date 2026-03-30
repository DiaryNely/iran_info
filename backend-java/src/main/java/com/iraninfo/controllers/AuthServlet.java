package com.iraninfo.controllers;

import com.iraninfo.filters.JwtAuthFilter;
import com.iraninfo.services.AuthService;
import com.iraninfo.utils.JsonUtil;
import com.iraninfo.utils.RequestUtil;
import com.iraninfo.utils.ResponseUtil;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.Map;

@WebServlet("/api/auth/*")
public class AuthServlet extends HttpServlet {

    private final AuthService authService = JwtAuthFilter.getAuthService();

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        String pathParam = RequestUtil.getPathParam(req);
        if (pathParam == null) {
            ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
            return;
        }

        try {
            switch (pathParam) {
                case "register" -> handleRegister(req, resp);
                case "login" -> handleLogin(req, resp);
                case "logout" -> handleLogout(req, resp);
                default -> ResponseUtil.sendError(resp, 404, "Not found", req.getRequestURI());
            }
        } catch (IllegalArgumentException e) {
            ResponseUtil.sendError(resp, 400, e.getMessage(), req.getRequestURI());
        } catch (SecurityException e) {
            ResponseUtil.sendError(resp, 401, e.getMessage(), req.getRequestURI());
        } catch (Exception e) {
            e.printStackTrace();
            ResponseUtil.sendError(resp, 500, "Internal server error", req.getRequestURI());
        }
    }

    private void handleRegister(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String body = RequestUtil.readBody(req);
        Map<String, Object> data = JsonUtil.getMapper().readValue(body, Map.class);

        String username = getString(data, "username");
        String email = getString(data, "email");
        String password = getString(data, "password");

        if (username == null || username.length() < 3) {
            throw new IllegalArgumentException("Username must be at least 3 characters");
        }
        if (email == null || !email.contains("@")) {
            throw new IllegalArgumentException("Invalid email");
        }
        if (password == null || password.length() < 8) {
            throw new IllegalArgumentException("Password must be at least 8 characters");
        }

        Map<String, Object> result = authService.register(username, email, password);
        ResponseUtil.sendJson(resp, 201, result);
    }

    private void handleLogin(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String body = RequestUtil.readBody(req);
        Map<String, Object> data = JsonUtil.getMapper().readValue(body, Map.class);

        String email = getString(data, "email");
        String password = getString(data, "password");

        if (email == null || !email.contains("@")) {
            throw new IllegalArgumentException("Invalid email");
        }
        if (password == null || password.length() < 8) {
            throw new IllegalArgumentException("Password must be at least 8 characters");
        }

        Map<String, Object> result = authService.login(email, password);
        ResponseUtil.sendJson(resp, 200, result);
    }

    private void handleLogout(HttpServletRequest req, HttpServletResponse resp) throws Exception {
        String token = RequestUtil.extractBearerToken(req);
        Map<String, Object> result = authService.logout(token != null ? token : "");
        ResponseUtil.sendJson(resp, 200, result);
    }

    private String getString(Map<String, Object> data, String key) {
        Object val = data.get(key);
        return val != null ? val.toString() : null;
    }
}
