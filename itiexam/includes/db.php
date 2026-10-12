<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (DB_DRIVER === 'mysql') {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    } else {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    db_migrate($pdo);
    return $pdo;
}

/** Creates tables on first run (works for SQLite and MySQL). */
function db_migrate(PDO $pdo): void
{
    $mysql = DB_DRIVER === 'mysql';
    $id = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $txt = $mysql ? 'MEDIUMTEXT' : 'TEXT';
    $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $id, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        created_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS trades (
        id $id, slug VARCHAR(120) NOT NULL UNIQUE, name VARCHAR(120) NOT NULL, full_name VARCHAR(255) NOT NULL DEFAULT '',
        short_desc VARCHAR(255) NOT NULL DEFAULT '', description $txt, content $txt,
        duration VARCHAR(60) NOT NULL DEFAULT '', nsqf_level VARCHAR(60) NOT NULL DEFAULT '',
        sector VARCHAR(120) NOT NULL DEFAULT '', exam_pattern VARCHAR(120) NOT NULL DEFAULT '',
        emoji VARCHAR(16) NOT NULL DEFAULT '', image VARCHAR(255) NOT NULL DEFAULT '', card_color VARCHAR(20) NOT NULL DEFAULT '#eef4ff',
        meta_title VARCHAR(255) NOT NULL DEFAULT '', meta_description VARCHAR(400) NOT NULL DEFAULT '', meta_keywords VARCHAR(500) NOT NULL DEFAULT '',
        is_popular INT NOT NULL DEFAULT 1, status VARCHAR(20) NOT NULL DEFAULT 'published', sort_order INT NOT NULL DEFAULT 0,
        created_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS trade_items (
        id $id, trade_id INT NOT NULL, section VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(1000) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', label VARCHAR(40) NOT NULL DEFAULT '',
        icon VARCHAR(40) NOT NULL DEFAULT '', questions INT NOT NULL DEFAULT 0, minutes INT NOT NULL DEFAULT 0,
        is_new INT NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id $id, slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(255) NOT NULL, content $txt,
        meta_title VARCHAR(255) NOT NULL DEFAULT '', meta_description VARCHAR(400) NOT NULL DEFAULT '', meta_keywords VARCHAR(500) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'published', created_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id $id, location VARCHAR(30) NOT NULL, parent_id INT NOT NULL DEFAULT 0, label VARCHAR(120) NOT NULL,
        url VARCHAR(500) NOT NULL, sort_order INT NOT NULL DEFAULT 0)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        skey VARCHAR(80) NOT NULL PRIMARY KEY, svalue $txt)$engine");
}
