package com.iraninfo.filters;

import jakarta.servlet.*;
import jakarta.servlet.annotation.WebFilter;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.Set;

@WebFilter("/*")
public class CorsFilter implements Filter {

    private static final Set<String> ALLOWED_ORIGINS;

    static {
        String configured = System.getenv("CORS_ORIGIN");
        if (configured != null && !configured.isEmpty()) {
            ALLOWED_ORIGINS = Set.of(configured.split(","));
        } else {
            ALLOWED_ORIGINS = Set.of("http://localhost:5173", "http://localhost:5174");
        }
    }

    @Override
    public void doFilter(ServletRequest request, ServletResponse response, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest req = (HttpServletRequest) request;
        HttpServletResponse resp = (HttpServletResponse) response;

        String origin = req.getHeader("Origin");

        if (origin != null && ALLOWED_ORIGINS.contains(origin.trim())) {
            resp.setHeader("Access-Control-Allow-Origin", origin.trim());
            resp.setHeader("Access-Control-Allow-Credentials", "true");
            resp.setHeader("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS");
            resp.setHeader("Access-Control-Allow-Headers",
                    "Origin, Content-Type, Accept, Authorization, X-Requested-With");
            resp.setHeader("Access-Control-Max-Age", "3600");
        }

        // Handle preflight
        if ("OPTIONS".equalsIgnoreCase(req.getMethod())) {
            resp.setStatus(HttpServletResponse.SC_OK);
            return;
        }

        chain.doFilter(request, response);
    }
}
