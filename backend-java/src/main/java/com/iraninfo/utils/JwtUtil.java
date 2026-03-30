package com.iraninfo.utils;

import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.security.Keys;
import javax.crypto.SecretKey;
import java.nio.charset.StandardCharsets;
import java.util.Date;
import java.util.Map;

public class JwtUtil {

    private static final String SECRET = System.getenv("JWT_SECRET") != null
            ? System.getenv("JWT_SECRET")
            : "change-me-in-production-change-me-in-production";
    private static final long EXPIRES_IN_SECONDS;

    static {
        String envExpires = System.getenv("JWT_EXPIRES_IN_SECONDS");
        EXPIRES_IN_SECONDS = envExpires != null ? Long.parseLong(envExpires) : 86400L;
    }

    private static SecretKey getSigningKey() {
        byte[] keyBytes = SECRET.getBytes(StandardCharsets.UTF_8);
        if (keyBytes.length < 32) {
            byte[] padded = new byte[32];
            System.arraycopy(keyBytes, 0, padded, 0, keyBytes.length);
            keyBytes = padded;
        }
        return Keys.hmacShaKeyFor(keyBytes);
    }

    private JwtUtil() {
    }

    public static String generateToken(long userId, String username, String role) {
        Date now = new Date();
        Date expiry = new Date(now.getTime() + EXPIRES_IN_SECONDS * 1000);

        return Jwts.builder()
                .subject(String.valueOf(userId))
                .claim("username", username)
                .claim("role", role)
                .issuedAt(now)
                .expiration(expiry)
                .signWith(getSigningKey())
                .compact();
    }

    public static Claims verifyToken(String token) {
        return Jwts.parser()
                .verifyWith(getSigningKey())
                .build()
                .parseSignedClaims(token)
                .getPayload();
    }

    public static long getUserId(Claims claims) {
        String subject = claims.getSubject();
        if (subject != null && !subject.isBlank()) {
            return Long.parseLong(subject);
        }

        Object sub = claims.get("sub");
        if (sub instanceof Number) {
            return ((Number) sub).longValue();
        }
        return Long.parseLong(sub.toString());
    }

    public static String getUsername(Claims claims) {
        return claims.get("username", String.class);
    }

    public static String getRole(Claims claims) {
        return claims.get("role", String.class);
    }
}
