package com.iraninfo.dao;

import com.iraninfo.models.User;
import java.sql.*;
import java.time.OffsetDateTime;

public class UserDao {

    public User findById(long id) throws SQLException {
        String sql = "SELECT id, username, email, password_hash, role, created_at, updated_at FROM users WHERE id = ?";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setLong(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    public User findByEmail(String email) throws SQLException {
        String sql = "SELECT id, username, email, password_hash, role, created_at, updated_at FROM users WHERE email = ?";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, email);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    public User findByUsername(String username) throws SQLException {
        String sql = "SELECT id, username, email, password_hash, role, created_at, updated_at FROM users WHERE username = ?";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, username);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    public User create(User user) throws SQLException {
        String sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?) " +
                     "RETURNING id, username, email, password_hash, role, created_at, updated_at";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, user.getUsername());
            ps.setString(2, user.getEmail());
            ps.setString(3, user.getPasswordHash());
            ps.setString(4, user.getRole());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        throw new SQLException("Failed to create user");
    }

    private User mapRow(ResultSet rs) throws SQLException {
        User u = new User();
        u.setId(rs.getLong("id"));
        u.setUsername(rs.getString("username"));
        u.setEmail(rs.getString("email"));
        u.setPasswordHash(rs.getString("password_hash"));
        u.setRole(rs.getString("role"));
        u.setCreatedAt(rs.getObject("created_at", OffsetDateTime.class));
        u.setUpdatedAt(rs.getObject("updated_at", OffsetDateTime.class));
        return u;
    }
}
