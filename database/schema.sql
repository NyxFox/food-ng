PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'editor')),
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS meal_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    original_normal_filename TEXT NOT NULL,
    original_vegetarian_filename TEXT NOT NULL,
    normal_source_type TEXT NOT NULL CHECK (normal_source_type IN ('pdf', 'docx')),
    vegetarian_source_type TEXT NOT NULL CHECK (vegetarian_source_type IN ('pdf', 'docx')),
    normal_storage_path TEXT NOT NULL,
    vegetarian_storage_path TEXT NOT NULL,
    merged_pdf_path TEXT NOT NULL,
    preview_status TEXT NOT NULL DEFAULT 'pending' CHECK (preview_status IN ('pending', 'approved', 'rejected')),
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'active', 'archived')),
    is_active INTEGER NOT NULL DEFAULT 0,
    conversion_notes TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    created_by INTEGER NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_meal_plans_active ON meal_plans (is_active);
CREATE INDEX IF NOT EXISTS idx_meal_plans_status ON meal_plans (status);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    updated_by INTEGER NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,
    action TEXT NOT NULL,
    message TEXT NOT NULL,
    context_json TEXT NULL,
    user_id INTEGER NULL,
    ip_address TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_logs_created_at ON logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_logs_action ON logs (action);
