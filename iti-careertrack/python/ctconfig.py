"""Shared database settings for the Python tools.

Values come from environment variables so that no password is stored in code.
Defaults match app/config/config.example.php for local development.
"""
import os

import pymysql
import pymysql.cursors


def db_settings() -> dict:
    return {
        "host": os.environ.get("CT_DB_HOST", "127.0.0.1"),
        "port": int(os.environ.get("CT_DB_PORT", "3306")),
        "user": os.environ.get("CT_DB_USER", "careertrack"),
        "password": os.environ.get("CT_DB_PASS", "ct_dev_pass"),
        "database": os.environ.get("CT_DB_NAME", "careertrack"),
    }


def connect(autocommit: bool = False) -> pymysql.connections.Connection:
    cfg = db_settings()
    return pymysql.connect(
        host=cfg["host"], port=cfg["port"], user=cfg["user"], password=cfg["password"],
        database=cfg["database"], charset="utf8mb4", autocommit=autocommit,
        cursorclass=pymysql.cursors.DictCursor,
    )
