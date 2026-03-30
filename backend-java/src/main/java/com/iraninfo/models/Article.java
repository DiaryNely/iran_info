package com.iraninfo.models;

import com.fasterxml.jackson.annotation.JsonProperty;
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.List;

public class Article {

    private long id;
    private String slug;
    private String title;
    private String content;

    @JsonProperty("coverImagePath")
    private String coverImagePath;

    @JsonProperty("coverImageAlt")
    private String coverImageAlt;

    @JsonProperty("galleryImages")
    private List<GalleryImage> galleryImages = new ArrayList<>();

    @JsonProperty("metaTitle")
    private String metaTitle;

    @JsonProperty("metaDescription")
    private String metaDescription;

    @JsonProperty("metaKeywords")
    private String metaKeywords;

    private String status;
    private boolean featured;

    @JsonProperty("publishedAt")
    private OffsetDateTime publishedAt;

    @JsonProperty("createdAt")
    private OffsetDateTime createdAt;

    @JsonProperty("updatedAt")
    private OffsetDateTime updatedAt;

    // Relations
    private User author;
    private List<Category> categories = new ArrayList<>();

    // Transient: used during create/update but not persisted directly
    @com.fasterxml.jackson.annotation.JsonIgnore
    private long userId;

    public Article() {}

    public long getId() { return id; }
    public void setId(long id) { this.id = id; }

    public String getSlug() { return slug; }
    public void setSlug(String slug) { this.slug = slug; }

    public String getTitle() { return title; }
    public void setTitle(String title) { this.title = title; }

    public String getContent() { return content; }
    public void setContent(String content) { this.content = content; }

    public String getCoverImagePath() { return coverImagePath; }
    public void setCoverImagePath(String coverImagePath) { this.coverImagePath = coverImagePath; }

    public String getCoverImageAlt() { return coverImageAlt; }
    public void setCoverImageAlt(String coverImageAlt) { this.coverImageAlt = coverImageAlt; }

    public List<GalleryImage> getGalleryImages() { return galleryImages; }
    public void setGalleryImages(List<GalleryImage> galleryImages) { this.galleryImages = galleryImages; }

    public String getMetaTitle() { return metaTitle; }
    public void setMetaTitle(String metaTitle) { this.metaTitle = metaTitle; }

    public String getMetaDescription() { return metaDescription; }
    public void setMetaDescription(String metaDescription) { this.metaDescription = metaDescription; }

    public String getMetaKeywords() { return metaKeywords; }
    public void setMetaKeywords(String metaKeywords) { this.metaKeywords = metaKeywords; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public boolean isFeatured() { return featured; }
    public void setFeatured(boolean featured) { this.featured = featured; }

    public OffsetDateTime getPublishedAt() { return publishedAt; }
    public void setPublishedAt(OffsetDateTime publishedAt) { this.publishedAt = publishedAt; }

    public OffsetDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(OffsetDateTime createdAt) { this.createdAt = createdAt; }

    public OffsetDateTime getUpdatedAt() { return updatedAt; }
    public void setUpdatedAt(OffsetDateTime updatedAt) { this.updatedAt = updatedAt; }

    public User getAuthor() { return author; }
    public void setAuthor(User author) { this.author = author; }

    public List<Category> getCategories() { return categories; }
    public void setCategories(List<Category> categories) { this.categories = categories; }

    public long getUserId() { return userId; }
    public void setUserId(long userId) { this.userId = userId; }
}
