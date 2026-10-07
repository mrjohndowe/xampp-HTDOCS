CREATE TABLE IF NOT EXISTS vehicle_makes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicle_models (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    make_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (make_id) REFERENCES vehicle_makes(id) ON DELETE CASCADE,
    UNIQUE (make_id, name)
);

CREATE INDEX IF NOT EXISTS idx_vehicle_models_make_id
ON vehicle_models(make_id);

CREATE INDEX IF NOT EXISTS idx_vehicle_makes_active
ON vehicle_makes(active);

CREATE INDEX IF NOT EXISTS idx_vehicle_models_active
ON vehicle_models(active);
