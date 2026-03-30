package com.iraninfo.utils;

import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.time.OffsetDateTime;
import java.time.ZoneOffset;
import java.util.LinkedHashMap;
import java.util.Map;

public class ResponseUtil {

    private ResponseUtil() {}

    public static void sendJson(HttpServletResponse resp, int status, Object body) throws IOException {
        resp.setStatus(status);
        resp.setContentType("application/json");
        resp.setCharacterEncoding("UTF-8");
        resp.getWriter().write(JsonUtil.toJson(body));
    }

    public static void sendError(HttpServletResponse resp, int status, String message, String path)
            throws IOException {
        Map<String, Object> error = new LinkedHashMap<>();
        error.put("statusCode", status);
        error.put("message", message);
        error.put("path", path);
        error.put("timestamp", OffsetDateTime.now(ZoneOffset.UTC).toString());
        sendJson(resp, status, error);
    }

    public static void sendError(HttpServletResponse resp, int status, Object message, String path)
            throws IOException {
        Map<String, Object> error = new LinkedHashMap<>();
        error.put("statusCode", status);
        error.put("message", message);
        error.put("path", path);
        error.put("timestamp", OffsetDateTime.now(ZoneOffset.UTC).toString());
        sendJson(resp, status, error);
    }
}
