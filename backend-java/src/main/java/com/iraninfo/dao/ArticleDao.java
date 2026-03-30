package com.iraninfo.dao;

import com.iraninfo.models.Article;
import com.iraninfo.models.Category;
import com.iraninfo.models.GalleryImage;
import com.iraninfo.models.User;
import com.iraninfo.utils.JsonUtil;
import com.fasterxml.jackson.core.type.TypeReference;

import java.sql.*;
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class ArticleDao {

    private static final String BASE_SELECT = "SELECT a.id, a.\"authorId\" AS user_id, a.title, a.content, a.cover_image_path, a.cover_image_alt, "
            +
            "a.gallery_images, a.slug, a.meta_title, a.meta_description, a.meta_keywords, " +
            "a.status, a.featured, NULL::timestamp AS published_at, a.created_at, a.updated_at, " +
            "u.id AS author_id, u.username AS author_username, u.email AS author_email, u.role AS author_role, " +
            "u.created_at AS author_created_at, u.updated_at AS author_updated_at " +
            "FROM articles a " +
            "JOIN users u ON a.\"authorId\" = u.id";

    public List<Article> findAllPublished() throws SQLException {
        String sql = BASE_SELECT + " WHERE a.status = 'published' ORDER BY a.featured DESC, a.created_at DESC";
        return queryArticlesWithCategories(sql);
    }

    public List<Article> findAllForAdmin() throws SQLException {
        String sql = BASE_SELECT + " ORDER BY a.created_at DESC";
        return queryArticlesWithCategories(sql);
    }

    public Article findBySlug(String slug) throws SQLException {
        String sql = BASE_SELECT + " WHERE a.slug = ? AND a.status = 'published'";
        try (Connection conn = ConnectionManager.getConnection();
                PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, slug);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    Article article = mapRow(rs);
                    article.setCategories(findCategoriesForArticle(conn, article.getId()));
                    return article;
                }
            }
        }
        return null;
    }

    public Article findById(long id) throws SQLException {
        String sql = BASE_SELECT + " WHERE a.id = ?";
        try (Connection conn = ConnectionManager.getConnection();
                PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setLong(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    Article article = mapRow(rs);
                    article.setCategories(findCategoriesForArticle(conn, article.getId()));
                    return article;
                }
            }
        }
        return null;
    }

    public boolean slugExists(String slug) throws SQLException {
        String sql = "SELECT 1 FROM articles WHERE slug = ?";
        try (Connection conn = ConnectionManager.getConnection();
                PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, slug);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next();
            }
        }
    }

    public Article create(Article article, List<Long> categoryIds) throws SQLException {
        String sql = "INSERT INTO articles (\"authorId\", title, content, cover_image_path, cover_image_alt, " +
                "gallery_images, slug, meta_title, meta_description, meta_keywords, status, featured) " +
                "VALUES (?, ?, ?, ?, ?, ?::jsonb, ?, ?, ?, ?, ?::articles_status_enum, ?) " +
                "RETURNING id, created_at, updated_at";
        try (Connection conn = ConnectionManager.getConnection()) {
            conn.setAutoCommit(false);
            try {
                try (PreparedStatement ps = conn.prepareStatement(sql)) {
                    ps.setLong(1, article.getUserId());
                    ps.setString(2, article.getTitle());
                    ps.setString(3, article.getContent());
                    ps.setString(4, article.getCoverImagePath());
                    ps.setString(5, article.getCoverImageAlt());
                    ps.setString(6, JsonUtil.toJson(article.getGalleryImages()));
                    ps.setString(7, article.getSlug());
                    ps.setString(8, article.getMetaTitle());
                    ps.setString(9, article.getMetaDescription());
                    ps.setString(10, article.getMetaKeywords());
                    ps.setString(11, article.getStatus());
                    ps.setBoolean(12, article.isFeatured());

                    try (ResultSet rs = ps.executeQuery()) {
                        if (rs.next()) {
                            article.setId(rs.getLong("id"));
                            article.setCreatedAt(rs.getObject("created_at", OffsetDateTime.class));
                            article.setUpdatedAt(rs.getObject("updated_at", OffsetDateTime.class));
                        }
                    }
                }

                // Insert article_category relations
                insertArticleCategories(conn, article.getId(), categoryIds);
                conn.commit();

                // Reload full article with relations
                return findById(article.getId());
            } catch (SQLException e) {
                conn.rollback();
                throw e;
            } finally {
                conn.setAutoCommit(true);
            }
        }
    }

    public Article update(Article article, List<Long> categoryIds) throws SQLException {
        String sql = "UPDATE articles SET title = ?, content = ?, cover_image_path = ?, cover_image_alt = ?, " +
                "gallery_images = ?::jsonb, slug = ?, meta_title = ?, meta_description = ?, " +
                "meta_keywords = ?, status = ?::articles_status_enum, featured = ?, updated_at = NOW() " +
                "WHERE id = ? RETURNING updated_at";
        try (Connection conn = ConnectionManager.getConnection()) {
            conn.setAutoCommit(false);
            try {
                try (PreparedStatement ps = conn.prepareStatement(sql)) {
                    ps.setString(1, article.getTitle());
                    ps.setString(2, article.getContent());
                    ps.setString(3, article.getCoverImagePath());
                    ps.setString(4, article.getCoverImageAlt());
                    ps.setString(5, JsonUtil.toJson(article.getGalleryImages()));
                    ps.setString(6, article.getSlug());
                    ps.setString(7, article.getMetaTitle());
                    ps.setString(8, article.getMetaDescription());
                    ps.setString(9, article.getMetaKeywords());
                    ps.setString(10, article.getStatus());
                    ps.setBoolean(11, article.isFeatured());
                    ps.setLong(12, article.getId());
                    ps.executeQuery();
                }

                if (categoryIds != null) {
                    // Delete old relations and insert new
                    try (PreparedStatement del = conn.prepareStatement(
                            "DELETE FROM article_category WHERE article_id = ?")) {
                        del.setLong(1, article.getId());
                        del.executeUpdate();
                    }
                    insertArticleCategories(conn, article.getId(), categoryIds);
                }

                conn.commit();
                return findById(article.getId());
            } catch (SQLException e) {
                conn.rollback();
                throw e;
            } finally {
                conn.setAutoCommit(true);
            }
        }
    }

    public void deleteById(long id) throws SQLException {
        String sql = "DELETE FROM articles WHERE id = ?";
        try (Connection conn = ConnectionManager.getConnection();
                PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setLong(1, id);
            ps.executeUpdate();
        }
    }

    // ─── Private helpers ─────────────────────────────────────────

    private List<Article> queryArticlesWithCategories(String sql) throws SQLException {
        Map<Long, Article> articleMap = new LinkedHashMap<>();
        try (Connection conn = ConnectionManager.getConnection();
                PreparedStatement ps = conn.prepareStatement(sql);
                ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                Article article = mapRow(rs);
                articleMap.put(article.getId(), article);
            }

            // Batch load categories for all articles
            if (!articleMap.isEmpty()) {
                loadCategoriesForArticles(conn, articleMap);
            }
        }
        return new ArrayList<>(articleMap.values());
    }

    private void loadCategoriesForArticles(Connection conn, Map<Long, Article> articleMap) throws SQLException {
        StringBuilder sb = new StringBuilder();
        sb.append("SELECT ac.article_id, c.id, c.name, c.slug, c.description, c.meta_title, ");
        sb.append("c.meta_description, c.created_at, c.updated_at ");
        sb.append("FROM article_category ac ");
        sb.append("JOIN categories c ON ac.category_id = c.id ");
        sb.append("WHERE ac.article_id IN (");
        int i = 0;
        for (Long id : articleMap.keySet()) {
            sb.append(i++ == 0 ? "?" : ", ?");
        }
        sb.append(")");

        try (PreparedStatement ps = conn.prepareStatement(sb.toString())) {
            int idx = 1;
            for (Long id : articleMap.keySet()) {
                ps.setLong(idx++, id);
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    long articleId = rs.getLong("article_id");
                    Category cat = mapCategory(rs);
                    Article article = articleMap.get(articleId);
                    if (article != null) {
                        article.getCategories().add(cat);
                    }
                }
            }
        }
    }

    private List<Category> findCategoriesForArticle(Connection conn, long articleId) throws SQLException {
        String sql = "SELECT c.id, c.name, c.slug, c.description, c.meta_title, c.meta_description, " +
                "c.created_at, c.updated_at " +
                "FROM categories c " +
                "JOIN article_category ac ON c.id = ac.category_id " +
                "WHERE ac.article_id = ?";
        List<Category> list = new ArrayList<>();
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setLong(1, articleId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    list.add(mapCategory(rs));
                }
            }
        }
        return list;
    }

    private void insertArticleCategories(Connection conn, long articleId, List<Long> categoryIds) throws SQLException {
        if (categoryIds == null || categoryIds.isEmpty())
            return;
        String sql = "INSERT INTO article_category (article_id, category_id) VALUES (?, ?)";
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            for (Long catId : categoryIds) {
                ps.setLong(1, articleId);
                ps.setLong(2, catId);
                ps.addBatch();
            }
            ps.executeBatch();
        }
    }

    private Article mapRow(ResultSet rs) throws SQLException {
        Article a = new Article();
        a.setId(rs.getLong("id"));
        a.setUserId(rs.getLong("user_id"));
        a.setTitle(rs.getString("title"));
        a.setContent(rs.getString("content"));
        a.setCoverImagePath(rs.getString("cover_image_path"));
        a.setCoverImageAlt(rs.getString("cover_image_alt"));
        a.setSlug(rs.getString("slug"));
        a.setMetaTitle(rs.getString("meta_title"));
        a.setMetaDescription(rs.getString("meta_description"));
        a.setMetaKeywords(rs.getString("meta_keywords"));
        a.setStatus(rs.getString("status"));
        a.setFeatured(rs.getBoolean("featured"));
        a.setPublishedAt(rs.getObject("published_at", OffsetDateTime.class));
        a.setCreatedAt(rs.getObject("created_at", OffsetDateTime.class));
        a.setUpdatedAt(rs.getObject("updated_at", OffsetDateTime.class));

        // Parse gallery_images JSONB
        String galleryJson = rs.getString("gallery_images");
        if (galleryJson != null && !galleryJson.isEmpty()) {
            try {
                List<GalleryImage> gallery = JsonUtil.getMapper().readValue(
                        galleryJson, new TypeReference<List<GalleryImage>>() {
                        });
                a.setGalleryImages(gallery);
            } catch (Exception e) {
                a.setGalleryImages(new ArrayList<>());
            }
        }

        // Map author
        User author = new User();
        author.setId(rs.getLong("author_id"));
        author.setUsername(rs.getString("author_username"));
        author.setEmail(rs.getString("author_email"));
        author.setRole(rs.getString("author_role"));
        author.setCreatedAt(rs.getObject("author_created_at", OffsetDateTime.class));
        author.setUpdatedAt(rs.getObject("author_updated_at", OffsetDateTime.class));
        a.setAuthor(author);

        return a;
    }

    private Category mapCategory(ResultSet rs) throws SQLException {
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
