package com.iraninfo.dao;

import com.iraninfo.models.Category;
import java.sql.*;
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.List;

public class CategoryDao {

    public List<Category> findAll() throws SQLException {
        String sql = "SELECT id, name, slug, description, meta_title, meta_description, created_at, updated_at " +
                     "FROM categories ORDER BY created_at DESC";
        List<Category> list = new ArrayList<>();
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                list.add(mapRow(rs));
            }
        }
        return list;
    }

    public Category findBySlug(String slug) throws SQLException {
        String sql = "SELECT id, name, slug, description, meta_title, meta_description, created_at, updated_at " +
                     "FROM categories WHERE slug = ?";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, slug);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    public Category findById(long id) throws SQLException {
        String sql = "SELECT id, name, slug, description, meta_title, meta_description, created_at, updated_at " +
                     "FROM categories WHERE id = ?";
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

    public List<Category> findByIds(List<Long> ids) throws SQLException {
        if (ids == null || ids.isEmpty()) {
            return new ArrayList<>();
        }
        StringBuilder sb = new StringBuilder();
        sb.append("SELECT id, name, slug, description, meta_title, meta_description, created_at, updated_at ");
        sb.append("FROM categories WHERE id IN (");
        for (int i = 0; i < ids.size(); i++) {
            sb.append(i == 0 ? "?" : ", ?");
        }
        sb.append(")");

        List<Category> list = new ArrayList<>();
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sb.toString())) {
            for (int i = 0; i < ids.size(); i++) {
                ps.setLong(i + 1, ids.get(i));
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    list.add(mapRow(rs));
                }
            }
        }
        return list;
    }

    public Category create(Category cat) throws SQLException {
        String sql = "INSERT INTO categories (name, slug, description, meta_title, meta_description) " +
                     "VALUES (?, ?, ?, ?, ?) " +
                     "RETURNING id, name, slug, description, meta_title, meta_description, created_at, updated_at";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, cat.getName());
            ps.setString(2, cat.getSlug());
            ps.setString(3, cat.getDescription());
            ps.setString(4, cat.getMetaTitle());
            ps.setString(5, cat.getMetaDescription());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        throw new SQLException("Failed to create category");
    }

    public Category update(Category cat) throws SQLException {
        String sql = "UPDATE categories SET name = ?, slug = ?, description = ?, meta_title = ?, " +
                     "meta_description = ?, updated_at = NOW() WHERE id = ? " +
                     "RETURNING id, name, slug, description, meta_title, meta_description, created_at, updated_at";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, cat.getName());
            ps.setString(2, cat.getSlug());
            ps.setString(3, cat.getDescription());
            ps.setString(4, cat.getMetaTitle());
            ps.setString(5, cat.getMetaDescription());
            ps.setLong(6, cat.getId());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        throw new SQLException("Failed to update category");
    }

    public void deleteById(long id) throws SQLException {
        String sql = "DELETE FROM categories WHERE id = ?";
        try (Connection conn = ConnectionManager.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setLong(1, id);
            ps.executeUpdate();
        }
    }

    private Category mapRow(ResultSet rs) throws SQLException {
        Category c = new Category();
        c.setId(rs.getLong("id"));
        c.setName(rs.getString("name"));
        c.setSlug(rs.getString("slug"));
        c.setDescription(rs.getString("description"));
        c.setMetaTitle(rs.getString("meta_title"));
        c.setMetaDescription(rs.getString("meta_description"));
        c.setCreatedAt(rs.getObject("created_at", OffsetDateTime.class));
        c.setUpdatedAt(rs.getObject("updated_at", OffsetDateTime.class));
        return c;
    }
}
