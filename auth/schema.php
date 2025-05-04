<?php
require_once __DIR__.'/db.php';
global $pdoSites;

/* users */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) UNIQUE NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    username      VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL DEFAULT NULL
  )");

/* sessions */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS sessions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    session_id   VARCHAR(128) NOT NULL,
    ip_address   VARCHAR(45),
    user_agent   TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )");

/* user_sites  – runs only if you use $pdoSites */
if (isset($pdoSites)) {
  $pdoSites->exec("
    CREATE TABLE IF NOT EXISTS user_sites (
      id           INT AUTO_INCREMENT PRIMARY KEY,
      user_id      INT NOT NULL,
      domain       VARCHAR(255) NOT NULL,
      filename     VARCHAR(255) NOT NULL,
      site_code    LONGTEXT NULL,
      created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY user_file (user_id, filename)
    )");
}
?>