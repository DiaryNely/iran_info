package com.iraninfo.models;

public class GalleryImage {

    private String path;
    private String alt;

    public GalleryImage() {}

    public GalleryImage(String path, String alt) {
        this.path = path;
        this.alt = alt;
    }

    public String getPath() { return path; }
    public void setPath(String path) { this.path = path; }

    public String getAlt() { return alt; }
    public void setAlt(String alt) { this.alt = alt; }
}
