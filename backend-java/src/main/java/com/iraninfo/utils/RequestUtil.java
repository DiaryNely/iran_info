package com.iraninfo.utils;

import jakarta.servlet.http.HttpServletRequest;
import java.io.BufferedReader;
import java.io.IOException;
import java.util.stream.Collectors;

public class RequestUtil {

    private RequestUtil() {}

    /**
     * Extract path segments after the servlet mapping.
     * For example, for /api/articles/5 with servlet mapped to /api/articles/*,
     * returns "5".
     */
    public static String getPathParam(HttpServletRequest req) {
        String pathInfo = req.getPathInfo();
        if (pathInfo == null || pathInfo.equals("/")) {
            return null;
        }
        // Remove leading slash
        return pathInfo.substring(1);
    }

    /**
     * Read the full request body as a String.
     */
    public static String readBody(HttpServletRequest req) throws IOException {
        try (BufferedReader reader = req.getReader()) {
            return reader.lines().collect(Collectors.joining("\n"));
        }
    }

    /**
     * Parse a path param as a long ID.
     * Returns -1 if parsing fails.
     */
    public static long parseIdFromPath(HttpServletRequest req) {
        String param = getPathParam(req);
        if (param == null) return -1;
        try {
            return Long.parseLong(param);
        } catch (NumberFormatException e) {
            return -1;
        }
    }

    /**
     * Extract the Bearer token from the Authorization header.
     */
    public static String extractBearerToken(HttpServletRequest req) {
        String auth = req.getHeader("Authorization");
        if (auth != null && auth.startsWith("Bearer ")) {
            return auth.substring("Bearer ".length()).trim();
        }
        return null;
    }
}
