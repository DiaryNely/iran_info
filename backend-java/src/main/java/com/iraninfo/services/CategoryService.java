package com.iraninfo.services;

import com.iraninfo.dao.CategoryDao;
import com.iraninfo.models.Category;

import java.sql.SQLException;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class CategoryService {

    private final CategoryDao categoryDao = new CategoryDao();

    public List<Category> findAll() throws SQLException {
        return categoryDao.findAll();
    }

    public Category findOneBySlug(String slug) throws SQLException {
        Category category = categoryDao.findBySlug(slug);
        if (category == null) {
            throw new IllegalStateException("Category not found");
        }
        return category;
    }

    public List<Category> findByIds(List<Long> ids) throws SQLException {
        if (ids == null || ids.isEmpty()) {
            return List.of();
        }
        List<Category> categories = categoryDao.findByIds(ids);
        if (categories.size() != ids.size()) {
            throw new IllegalArgumentException("One or more category IDs are invalid");
        }
        return categories;
    }

    public Category create(String name, String slug, String description, String metaTitle, String metaDescription)
            throws SQLException {
        Category existing = categoryDao.findBySlug(slug);
        if (existing != null) {
            throw new IllegalArgumentException("Category slug already exists");
        }

        Category cat = new Category();
        cat.setName(name);
        cat.setSlug(slug);
        cat.setDescription(description);
        cat.setMetaTitle(metaTitle);
        cat.setMetaDescription(metaDescription);
        return categoryDao.create(cat);
    }

    public Category update(long id, String name, String slug, String description,
                           String metaTitle, String metaDescription) throws SQLException {
        Category category = categoryDao.findById(id);
        if (category == null) {
            throw new IllegalStateException("Category not found");
        }

        if (slug != null && !slug.equals(category.getSlug())) {
            Category existingSlug = categoryDao.findBySlug(slug);
            if (existingSlug != null) {
                throw new IllegalArgumentException("Category slug already exists");
            }
        }

        category.setName(name != null ? name : category.getName());
        category.setSlug(slug != null ? slug : category.getSlug());
        category.setDescription(description != null ? description : category.getDescription());
        category.setMetaTitle(metaTitle != null ? metaTitle : category.getMetaTitle());
        category.setMetaDescription(metaDescription != null ? metaDescription : category.getMetaDescription());

        return categoryDao.update(category);
    }

    public Map<String, Object> remove(long id) throws SQLException {
        Category category = categoryDao.findById(id);
        if (category == null) {
            throw new IllegalStateException("Category not found");
        }
        categoryDao.deleteById(id);
        Map<String, Object> result = new LinkedHashMap<>();
        result.put("message", "Category deleted");
        return result;
    }
}
