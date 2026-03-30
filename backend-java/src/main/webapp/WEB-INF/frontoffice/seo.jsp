<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<script>
  (function () {
    const SITE_URL = 'https://iran-info.local';

    function ensureHeadTag(selector, createTag) {
      let tag = document.head.querySelector(selector);
      if (!tag) {
        tag = createTag();
        document.head.appendChild(tag);
      }
      return tag;
    }

    function toAbsoluteUrl(path) {
      const safePath = path || '/';
      const normalizedPath = safePath.startsWith('/') ? safePath : '/' + safePath;
      return SITE_URL + normalizedPath;
    }

    function buildSeoTitle(baseTitle) {
      const suffix = ' | Iran Info';
      const minLength = 50;
      const maxLength = 60;
      let title = baseTitle + suffix;

      if (title.length > maxLength) {
        const allowedBaseLength = maxLength - suffix.length - 1;
        const trimmedBase = baseTitle.slice(0, Math.max(allowedBaseLength, 10)).trim();
        title = trimmedBase + suffix;
      }

      if (title.length < minLength) {
        const padding = ' - actualites et analyses';
        title = (title + padding).slice(0, maxLength);
      }

      return title;
    }

    function buildSeoDescription(source, fallback) {
      const minLength = 150;
      const maxLength = 160;
      const base = String(source || '').replace(/\s+/g, ' ').trim();

      if (!base) {
        return fallback;
      }

      if (base.length > maxLength) {
        return base.slice(0, maxLength - 1).trim() + '.';
      }

      if (base.length < minLength) {
        const supplement = ' ' + fallback;
        return (base + supplement).slice(0, maxLength).trim();
      }

      return base;
    }

    function setDocumentSeo(config) {
      const title = config.title;
      const description = config.description;
      const path = config.path;
      const type = config.type || 'website';

      document.title = title;

      const descriptionTag = ensureHeadTag('meta[name="description"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('name', 'description');
        return meta;
      });
      descriptionTag.setAttribute('content', description);

      const canonicalTag = ensureHeadTag('link[rel="canonical"]', function () {
        const link = document.createElement('link');
        link.setAttribute('rel', 'canonical');
        return link;
      });
      canonicalTag.setAttribute('href', toAbsoluteUrl(path));

      const robotsTag = ensureHeadTag('meta[name="robots"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('name', 'robots');
        return meta;
      });
      robotsTag.setAttribute('content', 'index, follow, max-snippet:-1, max-image-preview:large');

      const viewportTag = ensureHeadTag('meta[name="viewport"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('name', 'viewport');
        return meta;
      });
      viewportTag.setAttribute('content', 'width=device-width, initial-scale=1');

      const ogTitle = ensureHeadTag('meta[property="og:title"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('property', 'og:title');
        return meta;
      });
      ogTitle.setAttribute('content', title);

      const ogDescription = ensureHeadTag('meta[property="og:description"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('property', 'og:description');
        return meta;
      });
      ogDescription.setAttribute('content', description);

      const ogType = ensureHeadTag('meta[property="og:type"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('property', 'og:type');
        return meta;
      });
      ogType.setAttribute('content', type);

      const ogUrl = ensureHeadTag('meta[property="og:url"]', function () {
        const meta = document.createElement('meta');
        meta.setAttribute('property', 'og:url');
        return meta;
      });
      ogUrl.setAttribute('content', toAbsoluteUrl(path));
    }

    function upsertJsonLd(scriptId, payload) {
      let script = document.getElementById(scriptId);
      if (!script) {
        script = document.createElement('script');
        script.type = 'application/ld+json';
        script.id = scriptId;
        document.head.appendChild(script);
      }
      script.textContent = JSON.stringify(payload);
    }

    function removeJsonLd(scriptId) {
      const script = document.getElementById(scriptId);
      if (script) {
        script.remove();
      }
    }

    function optimizeImageUrl(url, width) {
      if (!url || typeof url !== 'string') {
        return url;
      }

      if (!url.includes('images.unsplash.com')) {
        return url;
      }

      const joiner = url.includes('?') ? '&' : '?';
      return url + joiner + 'auto=format&fit=crop&w=' + width + '&q=75';
    }

    window.SeoTools = {
      buildSeoTitle,
      buildSeoDescription,
      setDocumentSeo,
      upsertJsonLd,
      removeJsonLd,
      optimizeImageUrl,
    };
  })();
</script>
