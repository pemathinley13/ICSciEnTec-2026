-- Default reference data. Safe to re-run (uses ON DUPLICATE KEY).

INSERT INTO roles (id, name) VALUES
  (1, 'author'),
  (2, 'organizing_committee')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO tracks (id, name, description) VALUES
  (1, 'Power Systems, Renewable Energy & Electrification', NULL),
  (2, 'Artificial Intelligence, Computing & Digital Transformation', NULL),
  (3, 'Resilient Infrastructure, Environmental Policy & Climate Science', NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- No admin user is seeded here on purpose: a hand-written password hash in a
-- SQL file is either fake (useless) or a known committed secret (unsafe).
-- Instead, run `php database/create_admin.php` once after importing this
-- file (or use public/setup_admin.php if you have no terminal access) — it
-- calls PHP's own password_hash() at runtime on the real server.
