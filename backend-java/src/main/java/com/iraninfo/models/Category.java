package com.iraninfo.models;

import com.fasterxml.jackson.annotation.JsonProperty;
import java.time.OffsetDateTime;

public class Category {

    private long id;
    private String name;
    private String slug;
    private String description;

    @JsonProperty("metaTitle")
    private String metaTitle;

    @JsonProperty("metaDescription")
    private String metaDescription;

    @JsonProperty("createdAt")
    private OffsetDateTime createdAt;

    @JsonProperty("updatedAt")
    private OffsetDateTime updatedAt;

    public Category() {}

    public long getId() { return id; }
    public void setId(long id) { this.id = id; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getSlug() { return slug; }
    public void setSlug(String slug) { this.slug = slug; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public String getMetaTitle() { return metaTitle; }
    public void setMetaTitle(String metaTitle) { this.metaTitle = metaTitle; }

    public String getMetaDescription() { return metaDescription; }
    public void setMetaDescription(String metaDescription) { this.metaDescription = metaDescription; }

    public OffsetDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(OffsetDateTime createdAt) { this.createdAt = createdAt; }

    public OffsetDateTime getUpdatedAt() { return updatedAt; }
    public void setUpdatedAt(OffsetDateTime updatedAt) { this.updatedAt = updatedAt; }
}
