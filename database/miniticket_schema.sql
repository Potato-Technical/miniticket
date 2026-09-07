-- MiniTicket — Schéma MySQL (MPD)
-- Construit à partir du MLD validé (01_cadrage.md §6.1)
-- Toutes les FK en ON DELETE RESTRICT — aucune suppression de user/catégorie/ticket prévue dans le MVP

CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pseudo         VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('USER','TECHNICIAN','ADMIN') NOT NULL DEFAULT 'USER',
    created_at     DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    technician_id  INT UNSIGNED NULL,
    category_id    INT UNSIGNED NOT NULL,
    type           ENUM('INCIDENT','DEMANDE') NOT NULL,
    impact         ENUM('FAIBLE','MOYEN','ELEVE') NOT NULL,
    urgence        ENUM('FAIBLE','MOYENNE','ELEVEE') NOT NULL,
    priorite       ENUM('P1','P2','P3','P4') NOT NULL,
    statut         ENUM('NOUVEAU','EN_COURS','RESOLU','FERME') NOT NULL DEFAULT 'NOUVEAU',
    titre          VARCHAR(150) NOT NULL,
    description    TEXT NOT NULL,
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    CONSTRAINT fk_tickets_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_technician
        FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_tickets_user_id (user_id),
    INDEX idx_tickets_technician_id (technician_id),
    INDEX idx_tickets_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    contenu     TEXT NOT NULL,
    created_at  DATETIME NOT NULL,
    CONSTRAINT fk_comments_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE RESTRICT,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed des 6 catégories fixes (01_cadrage.md §5) — pas de CRUD applicatif
INSERT INTO categories (libelle) VALUES
    ('Matériel'),
    ('Logiciel'),
    ('Réseau / Internet'),
    ('Compte / Accès'),
    ('Messagerie'),
    ('Autre');
