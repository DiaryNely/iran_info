<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%
  String appEntry = System.getenv("APP_ENTRY");
  if ("backoffice".equalsIgnoreCase(appEntry)) {
    response.sendRedirect(request.getContextPath() + "/backoffice/login");
  } else if ("api".equalsIgnoreCase(appEntry)) {
    response.sendRedirect(request.getContextPath() + "/api/health");
  } else {
    request.getRequestDispatcher("/WEB-INF/frontoffice/HomeFrontPage.jsp").forward(request, response);
  }
%>
