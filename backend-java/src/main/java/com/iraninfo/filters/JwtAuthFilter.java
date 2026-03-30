package com.iraninfo.filters;

import com.iraninfo.services.AuthService;
import com.iraninfo.utils.JwtUtil;
import com.iraninfo.utils.ResponseUtil;
import io.jsonwebtoken.Claims;
import jakarta.servlet.*;
import jakarta.servlet.annotation.WebFilter;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.Set;

@WebFilter("/*")
public class JwtAuthFilter implements Filter {

    private static final AuthService authService = new AuthService();

    /**
     * Paths that require JWT authentication.
     * We check prefixes and method combinations.
     */
    private static final Set<String> PUBLIC_PATHS = Set.of(
            "/api/health",
            "/api/auth/register",
            "/api/auth/login",
            "/api/auth/logout"
    );

    public static AuthService getAuthService() {
        return authService;
    }

    @Override
    public void doFilter(ServletRequest request, ServletResponse response, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest req = (HttpServletRequest) request;
        HttpServletResponse resp = (HttpServletResponse) response;

        String path = req.getRequestURI();
        String method = req.getMethod();

        // Skip OPTIONS (preflight)
        if ("OPTIONS".equalsIgnoreCase(method)) {
            chain.doFilter(request, response);
            return;
        }

        // Check if this path+method combo requires auth
        if (!requiresAuth(path, method)) {
            chain.doFilter(request, response);
            return;
        }

        // Extract and validate JWT
        String authHeader = req.getHeader("Authorization");
        if (authHeader == null || !authHeader.startsWith("Bearer ")) {
            ResponseUtil.sendError(resp, 401, "Missing bearer token", path);
            return;
        }

        String token = authHeader.substring("Bearer ".length()).trim();

        if (authService.isTokenRevoked(token)) {
            ResponseUtil.sendError(resp, 401, "Token has been revoked", path);
            return;
        }

        try {
            Claims claims = JwtUtil.verifyToken(token);
            // Store user info in request attributes
            req.setAttribute("userId", JwtUtil.getUserId(claims));
            req.setAttribute("username", JwtUtil.getUsername(claims));
            req.setAttribute("role", JwtUtil.getRole(claims));
            chain.doFilter(request, response);
        } catch (Exception e) {
            ResponseUtil.sendError(resp, 401, "Invalid or expired token", path);
        }
    }

    private boolean requiresAuth(String path, String method) {
        // Public endpoints
        if (PUBLIC_PATHS.contains(path)) {
            return false;
        }

        // Static file serving
        if (path.startsWith("/uploads/")) {
            return false;
        }

        // Public GET endpoints
        if ("GET".equalsIgnoreCase(method)) {
            // GET /api/articles (list published)
            if (path.equals("/api/articles")) return false;
            // GET /api/article/{slug}
            if (path.startsWith("/api/article/")) return false;
            // GET /api/categories
            if (path.equals("/api/categories")) return false;
            // GET /api/categorie/{slug}
            if (path.startsWith("/api/categorie/")) return false;
        }

        // Everything else under /api/ requires auth
        return path.startsWith("/api/");
    }
}
