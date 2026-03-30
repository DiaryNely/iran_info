package com.iraninfo.services;

import com.iraninfo.dao.ArticleDao;
import com.iraninfo.models.Article;
import com.iraninfo.models.GalleryImage;
import com.iraninfo.models.User;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.sql.SQLException;
import java.util.*;

import jakarta.servlet.http.Part;

public class ArticleService {

    private final ArticleDao articleDao = new ArticleDao();
    private final UserService userService = new UserService();
    private final CategoryService categoryService = new CategoryService();

    private static final String UPLOADS_DIR;

    static {
        String uploadsRoot = System.getenv("UPLOADS_DIR");
        UPLOADS_DIR = uploadsRoot != null ? uploadsRoot : System.getProperty("user.dir") + "/uploads/articles";
        new File(UPLOADS_DIR).mkdirs();
    }

    public List<Article> findAllPublished() throws SQLException {
        return articleDao.findAllPublished();
    }

    public List<Article> findAllForAdmin() throws SQLException {
        return articleDao.findAllForAdmin();
    }

    public Article findOneBySlug(String slug) throws SQLException {
        Article article = articleDao.findBySlug(slug);
        if (article == null) {
            throw new IllegalStateException("Article not found");
        }
        return article;
    }

    public Article create(Map<String, String> fields, long userId,
            Part coverImagePart, List<Part> galleryParts) throws SQLException, IOException {
        String slug = fields.get("slug");
        if (articleDao.slugExists(slug)) {
            throw new IllegalArgumentException("Article slug already exists");
        }

        User user = userService.findById(userId);
        if (user == null) {
            throw new IllegalStateException("Author not found");
        }

        // Parse category IDs
        List<Long> categoryIds = parseCategoryIds(fields.get("categoryIds"));
        categoryService.findByIds(categoryIds); // validates they exist

        // Handle cover image
        if (coverImagePart == null || coverImagePart.getSize() == 0) {
            throw new IllegalArgumentException("Cover image is required");
        }
        String coverImageAlt = fields.getOrDefault("coverImageAlt", "").trim();
        if (coverImageAlt.isEmpty()) {
            throw new IllegalArgumentException("Cover image alt text is required");
        }
        String coverPath = saveUploadedFile(coverImagePart);

        // Handle gallery images
        List<String> galleryAlts = parseStringArray(fields.get("galleryAlts"));
        if (galleryParts.size() != galleryAlts.size()) {
            throw new IllegalArgumentException("Each gallery image must have an alt text");
        }
        List<GalleryImage> galleryImages = new ArrayList<>();
        for (int i = 0; i < galleryParts.size(); i++) {
            String alt = galleryAlts.get(i).trim();
            if (alt.isEmpty()) {
                throw new IllegalArgumentException("Gallery image alt text is required");
            }
            String path = saveUploadedFile(galleryParts.get(i));
            galleryImages.add(new GalleryImage(path, alt));
        }

        // Build article
        Article article = new Article();
        article.setUserId(userId);
        article.setTitle(fields.get("title"));
        article.setContent(fields.get("content"));
        article.setSlug(slug);
        article.setCoverImagePath(coverPath);
        article.setCoverImageAlt(coverImageAlt);
        article.setGalleryImages(galleryImages);
        article.setMetaTitle(fields.get("metaTitle"));
        article.setMetaDescription(fields.get("metaDescription"));
        article.setMetaKeywords(fields.get("metaKeywords"));
        article.setStatus("published");

        String featuredStr = fields.getOrDefault("featured", "false");
        article.setFeatured("true".equals(featuredStr) || "1".equals(featuredStr));

        validateArticleFields(article);

        return articleDao.create(article, categoryIds);
    }

    public Article update(long id, Map<String, String> fields,
            Part coverImagePart, List<Part> galleryParts) throws SQLException, IOException {
        Article article = articleDao.findById(id);
        if (article == null) {
            throw new IllegalStateException("Article not found");
        }

        // Check slug uniqueness
        String newSlug = fields.get("slug");
        if (newSlug != null && !newSlug.equals(article.getSlug())) {
            if (articleDao.slugExists(newSlug)) {
                throw new IllegalArgumentException("Article slug already exists");
            }
        }

        // Update categories if provided
        List<Long> categoryIds = null;
        String categoryIdsStr = fields.get("categoryIds");
        if (categoryIdsStr != null) {
            categoryIds = parseCategoryIds(categoryIdsStr);
            categoryService.findByIds(categoryIds);
        }

        // Handle removed gallery images
        List<String> removedGalleryPaths = parseStringArray(fields.get("removedGalleryPaths"));
        List<GalleryImage> currentGallery = article.getGalleryImages() != null
                ? new ArrayList<>(article.getGalleryImages())
                : new ArrayList<>();

        List<GalleryImage> remainingGallery = new ArrayList<>();
        for (GalleryImage gi : currentGallery) {
            if (!removedGalleryPaths.contains(gi.getPath())) {
                remainingGallery.add(gi);
            }
        }
        for (String removedPath : removedGalleryPaths) {
            removeLocalImage(removedPath);
        }

        // Handle new gallery images
        List<String> galleryAlts = parseStringArray(fields.get("galleryAlts"));
        if (galleryParts.size() != galleryAlts.size()) {
            throw new IllegalArgumentException("Each gallery image must have an alt text");
        }
        List<GalleryImage> newGalleryImages = new ArrayList<>();
        for (int i = 0; i < galleryParts.size(); i++) {
            String alt = galleryAlts.get(i).trim();
            if (alt.isEmpty()) {
                throw new IllegalArgumentException("Gallery image alt text is required");
            }
            String path = saveUploadedFile(galleryParts.get(i));
            newGalleryImages.add(new GalleryImage(path, alt));
        }

        // Handle cover image
        String nextCoverImagePath = article.getCoverImagePath();
        String nextCoverImageAlt = article.getCoverImageAlt();

        String removeCoverStr = fields.getOrDefault("removeCoverImage", "false");
        boolean removeCover = "true".equals(removeCoverStr) || "1".equals(removeCoverStr);

        if (removeCover) {
            if (nextCoverImagePath != null) {
                removeLocalImage(nextCoverImagePath);
            }
            nextCoverImagePath = null;
            nextCoverImageAlt = null;
        }

        if (coverImagePart != null && coverImagePart.getSize() > 0) {
            String coverImageAlt = fields.getOrDefault("coverImageAlt", "").trim();
            if (coverImageAlt.isEmpty()) {
                throw new IllegalArgumentException("Cover image alt text is required when replacing cover image");
            }
            if (nextCoverImagePath != null) {
                removeLocalImage(nextCoverImagePath);
            }
            nextCoverImagePath = saveUploadedFile(coverImagePart);
            nextCoverImageAlt = coverImageAlt;
        } else if (fields.containsKey("coverImageAlt")) {
            String coverImageAlt = fields.get("coverImageAlt").trim();
            if (coverImageAlt.isEmpty()) {
                throw new IllegalArgumentException("Cover image alt text cannot be empty");
            }
            nextCoverImageAlt = coverImageAlt;
        }

        if (nextCoverImagePath == null || nextCoverImageAlt == null) {
            throw new IllegalArgumentException("Cover image and its alt text are required");
        }

        // Apply updates
        article.setTitle(fields.getOrDefault("title", article.getTitle()));
        article.setContent(fields.getOrDefault("content", article.getContent()));
        article.setSlug(newSlug != null ? newSlug : article.getSlug());
        article.setMetaTitle(fields.getOrDefault("metaTitle", article.getMetaTitle()));
        article.setMetaDescription(fields.getOrDefault("metaDescription", article.getMetaDescription()));
        article.setMetaKeywords(fields.getOrDefault("metaKeywords", article.getMetaKeywords()));
        article.setStatus("published");
        article.setCoverImagePath(nextCoverImagePath);
        article.setCoverImageAlt(nextCoverImageAlt);

        String featuredStr = fields.get("featured");
        if (featuredStr != null) {
            article.setFeatured("true".equals(featuredStr) || "1".equals(featuredStr));
        }

        List<GalleryImage> finalGallery = new ArrayList<>(remainingGallery);
        finalGallery.addAll(newGalleryImages);
        article.setGalleryImages(finalGallery);

        validateArticleFields(article);

        return articleDao.update(article, categoryIds);
    }

    public Map<String, Object> remove(long id) throws SQLException {
        Article article = articleDao.findById(id);
        if (article == null) {
            throw new IllegalStateException("Article not found");
        }

        // Remove uploaded images
        if (article.getCoverImagePath() != null) {
            removeLocalImage(article.getCoverImagePath());
        }
        if (article.getGalleryImages() != null) {
            for (GalleryImage gi : article.getGalleryImages()) {
                removeLocalImage(gi.getPath());
            }
        }

        articleDao.deleteById(id);
        Map<String, Object> result = new LinkedHashMap<>();
        result.put("message", "Article deleted");
        return result;
    }

    // ─── Private Helpers ────────────────────────────────────────

    private String saveUploadedFile(Part part) throws IOException {
        String originalName = getFileName(part);
        String extension = "";
        int dotIndex = originalName.lastIndexOf('.');
        if (dotIndex >= 0) {
            extension = originalName.substring(dotIndex).toLowerCase();
            originalName = originalName.substring(0, dotIndex);
        }

        String baseName = sanitizeName(originalName);
        String uniqueName = System.currentTimeMillis() + "-" +
                Math.round(Math.random() * 1_000_000_000) + "-" + baseName + extension;

        Path targetPath = Paths.get(UPLOADS_DIR, uniqueName);
        try (InputStream in = part.getInputStream()) {
            Files.copy(in, targetPath, StandardCopyOption.REPLACE_EXISTING);
        }
        return "/uploads/articles/" + uniqueName;
    }

    private String sanitizeName(String name) {
        return java.text.Normalizer.normalize(name, java.text.Normalizer.Form.NFD)
                .replaceAll("[\\u0300-\\u036f]", "")
                .replaceAll("[^a-zA-Z0-9\\-_]", "-")
                .replaceAll("-+", "-")
                .toLowerCase();
    }

    private String getFileName(Part part) {
        String contentDisp = part.getHeader("content-disposition");
        if (contentDisp != null) {
            for (String cd : contentDisp.split(";")) {
                cd = cd.trim();
                if (cd.startsWith("filename")) {
                    return cd.substring(cd.indexOf('=') + 1).trim()
                            .replace("\"", "");
                }
            }
        }
        return "image";
    }

    private void removeLocalImage(String uploadPath) {
        if (uploadPath == null || !uploadPath.startsWith("/uploads/articles/"))
            return;
        String relative = uploadPath.replace("/uploads/articles/", "");
        Path fullPath = Paths.get(UPLOADS_DIR, relative);
        try {
            Files.deleteIfExists(fullPath);
        } catch (IOException e) {
            // Ignore missing file errors to keep resilient
        }
    }

    private List<Long> parseCategoryIds(String raw) {
        if (raw == null || raw.isEmpty())
            return new ArrayList<>();
        List<Long> ids = new ArrayList<>();
        // Try JSON array first
        String trimmed = raw.trim();
        if (trimmed.startsWith("[")) {
            trimmed = trimmed.substring(1, trimmed.length() - 1);
        }
        for (String part : trimmed.split(",")) {
            part = part.trim().replace("\"", "");
            if (!part.isEmpty()) {
                try {
                    ids.add(Long.parseLong(part));
                } catch (NumberFormatException ignored) {
                }
            }
        }
        return ids;
    }

    private List<String> parseStringArray(String raw) {
        if (raw == null || raw.isEmpty())
            return new ArrayList<>();
        List<String> result = new ArrayList<>();
        String trimmed = raw.trim();
        if (trimmed.startsWith("[")) {
            // JSON array
            try {
                String[] items = com.iraninfo.utils.JsonUtil.getMapper()
                        .readValue(trimmed, String[].class);
                result.addAll(Arrays.asList(items));
                return result;
            } catch (Exception e) {
                // Fallback to comma-split
            }
        }
        for (String part : trimmed.split(",")) {
            part = part.trim();
            if (!part.isEmpty()) {
                result.add(part);
            }
        }
        return result;
    }

    private void validateArticleFields(Article article) {
        requireNotBlank(article.getTitle(), "Title is required");
        requireNotBlank(article.getSlug(), "Slug is required");
        requireNotBlank(article.getContent(), "Content is required");
        requireNotBlank(article.getMetaTitle(), "Meta title is required");
        requireNotBlank(article.getMetaDescription(), "Meta description is required");
        requireNotBlank(article.getMetaKeywords(), "Meta keywords are required");
        requireNotBlank(article.getCoverImageAlt(), "Cover image alt text is required");

        requireMaxLength("title", article.getTitle(), 255);
        requireMaxLength("slug", article.getSlug(), 180);
        requireMaxLength("cover image alt text", article.getCoverImageAlt(), 160);
        requireMaxLength("meta title", article.getMetaTitle(), 60);
        requireMaxLength("meta description", article.getMetaDescription(), 160);
        requireMaxLength("meta keywords", article.getMetaKeywords(), 255);

        if (article.getGalleryImages() != null) {
            for (GalleryImage image : article.getGalleryImages()) {
                requireNotBlank(image.getAlt(), "Gallery image alt text is required");
                requireMaxLength("gallery image alt text", image.getAlt(), 160);
            }
        }
    }

    private void requireNotBlank(String value, String message) {
        if (value == null || value.trim().isEmpty()) {
            throw new IllegalArgumentException(message);
        }
    }

    private void requireMaxLength(String field, String value, int maxLen) {
        if (value != null && value.length() > maxLen) {
            throw new IllegalArgumentException(field + " must be at most " + maxLen + " characters");
        }
    }
}
