CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    school_year TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role TEXT NOT NULL CHECK(role IN ('admin','teacher','student')),
    full_name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    class_id INTEGER NULL,
    points INTEGER NOT NULL DEFAULT 0,
    streak INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_activity_at TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS student_levels (
    student_id INTEGER PRIMARY KEY,
    level INTEGER NOT NULL DEFAULT 1,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vocabulary (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    class_id INTEGER NULL,
    level INTEGER NOT NULL DEFAULT 1,
    theme TEXT NOT NULL,
    french_word TEXT NOT NULL,
    dutch_word TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS verbs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    class_id INTEGER NULL,
    level INTEGER NOT NULL DEFAULT 1,
    infinitive TEXT NOT NULL,
    translation TEXT NOT NULL,
    group_name TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS conjugations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    verb_id INTEGER NOT NULL,
    tense TEXT NOT NULL,
    pronoun TEXT NOT NULL,
    conjugated_form TEXT NOT NULL,
    FOREIGN KEY (verb_id) REFERENCES verbs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS class_workforms (
    class_id INTEGER PRIMARY KEY,
    flashcards INTEGER NOT NULL DEFAULT 1,
    multiple_choice INTEGER NOT NULL DEFAULT 1,
    typing INTEGER NOT NULL DEFAULT 1,
    conjugation INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exercise_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    exercise_type TEXT NOT NULL,
    item_type TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    workform TEXT NOT NULL,
    question TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    user_answer TEXT NOT NULL,
    is_correct INTEGER NOT NULL,
    attempted_at TEXT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
