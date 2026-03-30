package com.iraninfo.dao;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class ConnectionManager {

    private static final String HOST;
    private static final int PORT;
    private static final String DB_NAME;
    private static final String USER;
    private static final String PASSWORD;

    static {
        HOST = env("DATABASE_HOST", "localhost");
        PORT = Integer.parseInt(env("DATABASE_PORT", "5432"));
        DB_NAME = env("DATABASE_NAME", "iran_info");
        USER = env("DATABASE_USER", "postgres");
        PASSWORD = env("DATABASE_PASSWORD", "postgres");

        try {
            Class.forName("org.postgresql.Driver");
        } catch (ClassNotFoundException e) {
            throw new RuntimeException("PostgreSQL JDBC driver not found", e);
        }
    }

    private ConnectionManager() {}

    private static String env(String key, String defaultValue) {
        String value = System.getenv(key);
        return value != null ? value : defaultValue;
    }

    public static Connection getConnection() throws SQLException {
        String url = String.format("jdbc:postgresql://%s:%d/%s", HOST, PORT, DB_NAME);
        return DriverManager.getConnection(url, USER, PASSWORD);
    }
}
