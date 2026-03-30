package com.iraninfo.services;

import com.iraninfo.models.User;
import com.iraninfo.utils.JwtUtil;
import org.mindrot.jbcrypt.BCrypt;

import java.sql.SQLException;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

public class AuthService {

    private final UserService userService = new UserService();
    private final Set<String> revokedTokens = ConcurrentHashMap.newKeySet();

    public Map<String, Object> register(String username, String email, String password) throws SQLException {
        User existingEmail = userService.findByEmail(email);
        if (existingEmail != null) {
            throw new IllegalArgumentException("Email already in use");
        }

        User existingUsername = userService.findByUsername(username);
        if (existingUsername != null) {
            throw new IllegalArgumentException("Username already in use");
        }

        String passwordHash = BCrypt.hashpw(password, BCrypt.gensalt(10));
        User user = userService.create(username, email, passwordHash, "admin");

        Map<String, Object> result = new LinkedHashMap<>();
        result.put("id", user.getId());
        result.put("username", user.getUsername());
        result.put("email", user.getEmail());
        result.put("role", user.getRole());
        result.put("createdAt", user.getCreatedAt());
        return result;
    }

    public Map<String, Object> login(String email, String password) throws SQLException {
        User user = userService.findByEmail(email);
        if (user == null) {
            throw new SecurityException("Invalid credentials");
        }

        if (!BCrypt.checkpw(password, user.getPasswordHash())) {
            throw new SecurityException("Invalid credentials");
        }

        String accessToken = JwtUtil.generateToken(user.getId(), user.getUsername(), user.getRole());

        Map<String, Object> userMap = new LinkedHashMap<>();
        userMap.put("id", user.getId());
        userMap.put("username", user.getUsername());
        userMap.put("email", user.getEmail());
        userMap.put("role", user.getRole());

        Map<String, Object> result = new LinkedHashMap<>();
        result.put("accessToken", accessToken);
        result.put("tokenType", "Bearer");
        result.put("user", userMap);
        return result;
    }

    public Map<String, Object> logout(String token) {
        if (token == null || token.isEmpty()) {
            throw new IllegalArgumentException("Token is required");
        }
        revokedTokens.add(token);
        Map<String, Object> result = new LinkedHashMap<>();
        result.put("message", "Logged out successfully");
        return result;
    }

    public boolean isTokenRevoked(String token) {
        return revokedTokens.contains(token);
    }
}
