package com.iraninfo.controllers;

import java.io.IOException;
import java.util.Set;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

@WebServlet("/frontoffice/*")
public class FrontofficePageServlet extends HttpServlet {

    private static final Set<String> ALLOWED_PAGES = Set.of(
            "HomeFrontPage.jsp",
            "ArticleFrontPage.jsp",
            "PublicLayout.jsp");

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        String pathInfo = req.getPathInfo();

        if (pathInfo == null || "/".equals(pathInfo)) {
            resp.sendRedirect(req.getContextPath() + "/frontoffice/HomeFrontPage.jsp");
            return;
        }

        String page = pathInfo.startsWith("/") ? pathInfo.substring(1) : pathInfo;
        if (!ALLOWED_PAGES.contains(page)) {
            resp.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }

        req.getRequestDispatcher("/WEB-INF/frontoffice/" + page).forward(req, resp);
    }
}
