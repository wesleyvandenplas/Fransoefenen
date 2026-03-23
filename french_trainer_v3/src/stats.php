<?php
require_once __DIR__ . '/db.php';

function get_student_level(int $studentId): int {
    $row = db_query('SELECT level FROM student_levels WHERE student_id = :id', ['id' => $studentId])->fetch();
    return (int)($row['level'] ?? 1);
}

function get_student_stats(int $studentId): array {
    $stats = db_query('SELECT points, streak, last_activity_at FROM users WHERE id = :id', ['id' => $studentId])->fetch() ?: [];
    $done = db_query('SELECT COUNT(*) AS total, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct FROM exercise_attempts WHERE student_id = :id', ['id' => $studentId])->fetch() ?: [];
    return [
        'points' => (int)($stats['points'] ?? 0),
        'streak' => (int)($stats['streak'] ?? 0),
        'last_activity_at' => $stats['last_activity_at'] ?? null,
        'total' => (int)($done['total'] ?? 0),
        'correct' => (int)($done['correct'] ?? 0),
    ];
}

function get_student_weekly_activity(int $studentId): array {
    return db_query(
        'SELECT substr(attempted_at,1,10) AS day, COUNT(*) AS total,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct
         FROM exercise_attempts
         WHERE student_id = :id
         GROUP BY substr(attempted_at,1,10)
         ORDER BY day DESC
         LIMIT 7',
        ['id' => $studentId]
    )->fetchAll();
}

function get_class_leaderboard(int $classId, int $limit = 5): array {
    return db_query(
        'SELECT full_name, points, streak
         FROM users
         WHERE role = "student" AND class_id = :class_id
         ORDER BY points DESC, streak DESC, full_name ASC
         LIMIT ' . (int)$limit,
        ['class_id' => $classId]
    )->fetchAll();
}

function get_teacher_class_summary(int $classId): array {
    $row = db_query(
        'SELECT COUNT(*) AS students,
                COALESCE(SUM(points),0) AS total_points,
                COALESCE(AVG(points),0) AS avg_points,
                COALESCE(AVG(streak),0) AS avg_streak
         FROM users
         WHERE role = "student" AND class_id = :class_id',
        ['class_id' => $classId]
    )->fetch() ?: [];

    $attempts = db_query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct
         FROM exercise_attempts ea
         INNER JOIN users u ON u.id = ea.student_id
         WHERE u.class_id = :class_id',
        ['class_id' => $classId]
    )->fetch() ?: [];

    return [
        'students' => (int)($row['students'] ?? 0),
        'total_points' => (int)($row['total_points'] ?? 0),
        'avg_points' => (int)round((float)($row['avg_points'] ?? 0)),
        'avg_streak' => (int)round((float)($row['avg_streak'] ?? 0)),
        'total_attempts' => (int)($attempts['total'] ?? 0),
        'correct_attempts' => (int)($attempts['correct'] ?? 0),
    ];
}

function get_teacher_student_report(int $classId): array {
    return db_query(
        'SELECT u.id, u.full_name, u.username, u.points, u.streak,
                COALESCE(sl.level,1) AS level,
                COUNT(ea.id) AS attempts,
                SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct,
                MAX(ea.attempted_at) AS last_attempt
         FROM users u
         LEFT JOIN student_levels sl ON sl.student_id = u.id
         LEFT JOIN exercise_attempts ea ON ea.student_id = u.id
         WHERE u.role = "student" AND u.class_id = :class_id
         GROUP BY u.id, u.full_name, u.username, u.points, u.streak, sl.level
         ORDER BY u.full_name ASC',
        ['class_id' => $classId]
    )->fetchAll();
}

function get_teacher_theme_report(int $classId): array {
    return db_query(
        'SELECT v.theme AS label,
                COUNT(ea.id) AS attempts,
                SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct,
                COUNT(DISTINCT ea.student_id) AS active_students
         FROM exercise_attempts ea
         INNER JOIN users u ON u.id = ea.student_id
         INNER JOIN vocabulary v ON v.id = ea.item_id
         WHERE u.class_id = :class_id AND ea.item_type = "vocabulary"
         GROUP BY v.theme
         ORDER BY attempts DESC, label ASC',
        ['class_id' => $classId]
    )->fetchAll();
}

function get_student_theme_breakdown(int $studentId): array {
    return db_query(
        'SELECT v.theme AS label,
                COUNT(ea.id) AS attempts,
                SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct
         FROM exercise_attempts ea
         INNER JOIN vocabulary v ON v.id = ea.item_id
         WHERE ea.student_id = :student_id AND ea.item_type = "vocabulary"
         GROUP BY v.theme
         ORDER BY attempts DESC, label ASC',
        ['student_id' => $studentId]
    )->fetchAll();
}

function get_student_recent_mistakes(int $studentId, int $limit = 8): array {
    return db_query(
        'SELECT attempted_at, question, correct_answer, user_answer, workform
         FROM exercise_attempts
         WHERE student_id = :student_id AND is_correct = 0
         ORDER BY attempted_at DESC
         LIMIT ' . (int)$limit,
        ['student_id' => $studentId]
    )->fetchAll();
}

function get_teacher_student_risk_list(int $classId, int $limit = 6): array {
    $rows = db_query(
        'SELECT u.id, u.full_name, u.points, u.streak,
                COUNT(ea.id) AS attempts,
                SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct,
                MAX(ea.attempted_at) AS last_attempt
         FROM users u
         LEFT JOIN exercise_attempts ea ON ea.student_id = u.id
         WHERE u.role = "student" AND u.class_id = :class_id
         GROUP BY u.id, u.full_name, u.points, u.streak
         HAVING attempts > 0
         ORDER BY (CAST(SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS FLOAT) / COUNT(ea.id)) ASC, attempts DESC, u.full_name ASC
         LIMIT ' . (int)$limit,
        ['class_id' => $classId]
    )->fetchAll();

    return $rows;
}

function get_available_vocabulary_themes(?int $classId = null): array {
    if ($classId) {
        return db_query(
            'SELECT DISTINCT theme FROM vocabulary WHERE class_id IS NULL OR class_id = :class_id ORDER BY theme ASC',
            ['class_id' => $classId]
        )->fetchAll();
    }
    return db_query('SELECT DISTINCT theme FROM vocabulary ORDER BY theme ASC')->fetchAll();
}

function get_available_verb_groups(?int $classId = null): array {
    if ($classId) {
        return db_query(
            'SELECT DISTINCT COALESCE(group_name, "Algemeen") AS group_name FROM verbs WHERE class_id IS NULL OR class_id = :class_id ORDER BY group_name ASC',
            ['class_id' => $classId]
        )->fetchAll();
    }
    return db_query('SELECT DISTINCT COALESCE(group_name, "Algemeen") AS group_name FROM verbs ORDER BY group_name ASC')->fetchAll();
}

function record_attempt(int $studentId, string $exerciseType, string $itemType, int $itemId, string $mode, string $question, string $answer, string $userAnswer, bool $isCorrect): void {
    db_query(
        'INSERT INTO exercise_attempts (student_id, exercise_type, item_type, item_id, workform, question, correct_answer, user_answer, is_correct, attempted_at)
         VALUES (:student_id, :exercise_type, :item_type, :item_id, :workform, :question, :correct_answer, :user_answer, :is_correct, :attempted_at)',
        [
            'student_id' => $studentId,
            'exercise_type' => $exerciseType,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'workform' => $mode,
            'question' => $question,
            'correct_answer' => $answer,
            'user_answer' => $userAnswer,
            'is_correct' => $isCorrect ? 1 : 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]
    );

    $user = db_query('SELECT points, streak, last_activity_at FROM users WHERE id = :id', ['id' => $studentId])->fetch();
    $points = (int)($user['points'] ?? 0);
    $streak = (int)($user['streak'] ?? 0);
    $last = $user['last_activity_at'] ?? null;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $lastDate = $last ? date('Y-m-d', strtotime($last)) : null;
    if ($lastDate === $today) {
        $newStreak = max(1, $streak);
    } elseif ($lastDate === $yesterday) {
        $newStreak = $streak + 1;
    } else {
        $newStreak = 1;
    }

    $points += $isCorrect ? 10 : 2;

    db_query('UPDATE users SET points = :points, streak = :streak, last_activity_at = :last_activity_at WHERE id = :id', [
        'points' => $points,
        'streak' => $newStreak,
        'last_activity_at' => date('Y-m-d H:i:s'),
        'id' => $studentId,
    ]);
}

function get_available_workforms_for_class(?int $classId): array {
    $default = ['flashcards', 'multiple_choice', 'typing', 'conjugation'];
    if (!$classId) {
        return $default;
    }
    $row = db_query('SELECT * FROM class_workforms WHERE class_id = :id', ['id' => $classId])->fetch();
    if (!$row) {
        return $default;
    }
    $out = [];
    foreach ($default as $form) {
        if ((int)($row[$form] ?? 0) === 1) {
            $out[] = $form;
        }
    }
    return $out ?: $default;
}
