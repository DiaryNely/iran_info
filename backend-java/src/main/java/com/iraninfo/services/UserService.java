package com.iraninfo.services;

import com.iraninfo.dao.UserDao;
import com.iraninfo.models.User;
import java.sql.SQLException;

public class UserService {

    private final UserDao userDao = new UserDao();

    public User findById(long id) throws SQLException {
        return userDao.findById(id);
    }

    public User findByEmail(String email) throws SQLException {
        return userDao.findByEmail(email);
    }

    public User findByUsername(String username) throws SQLException {
        return userDao.findByUsername(username);
    }

    public User create(String username, String email, String passwordHash, String role) throws SQLException {
        User user = new User();
        user.setUsername(username);
        user.setEmail(email);
        user.setPasswordHash(passwordHash);
        user.setRole(role);
        return userDao.create(user);
    }
}
