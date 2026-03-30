package com.iraninfo.controllers;

import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.net.URLConnection;

@WebServlet("/uploads/*")
public class UploadServlet extends HttpServlet {

    private static final String UPLOADS_ROOT;

    static {
        String envDir = System.getenv("UPLOADS_DIR");
        if (envDir != null) {
            // UPLOADS_DIR points to /app/uploads/articles, we need the parent
            UPLOADS_ROOT = new File(envDir).getParent();
        } else {
            UPLOADS_ROOT = System.getProperty("user.dir") + "/uploads";
        }
    }

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        String pathInfo = req.getPathInfo();
        if (pathInfo == null || pathInfo.equals("/")) {
            resp.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }

        // Sanitize path to prevent directory traversal
        String safePath = pathInfo.replace("..", "").replace("\\", "/");
        File file = new File(UPLOADS_ROOT, safePath);

        if (!file.exists() || !file.isFile()) {
            resp.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }

        // Verify the file is under the uploads root
        if (!file.getCanonicalPath().startsWith(new File(UPLOADS_ROOT).getCanonicalPath())) {
            resp.sendError(HttpServletResponse.SC_FORBIDDEN);
            return;
        }

        // Set content type
        String contentType = URLConnection.guessContentTypeFromName(file.getName());
        if (contentType == null) contentType = "application/octet-stream";
        resp.setContentType(contentType);
        resp.setContentLengthLong(file.length());

        // Stream file
        try (FileInputStream fis = new FileInputStream(file);
             OutputStream out = resp.getOutputStream()) {
            byte[] buffer = new byte[8192];
            int bytesRead;
            while ((bytesRead = fis.read(buffer)) != -1) {
                out.write(buffer, 0, bytesRead);
            }
        }
    }
}
