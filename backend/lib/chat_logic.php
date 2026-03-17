<?php

// Логика простого "интеллектуального" чат-бота на основе таблицы bot_knowledge.

require_once __DIR__ . '/../config/db.php';

/**
 * Находит подходящий ответ бота по тексту вопроса пользователя.
 *
 * @param string $userMessage
 * @return array|null ['answer_text' => string, 'service_link' => string|null]
 */
function bot_find_answer(string $userMessage): ?array
{
    global $mysqli;

    $userMessageLower = mb_strtolower($userMessage, 'UTF-8');

    $stmt = $mysqli->prepare('SELECT id, question_pattern, answer_text, related_service_id, priority FROM bot_knowledge ORDER BY priority DESC');
    if (!$stmt) {
        return null;
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $bestRow = null;
    $bestScore = 0;

    while ($row = $result->fetch_assoc()) {
        $pattern = mb_strtolower($row['question_pattern'], 'UTF-8');
        $score = 0;

        $parts = preg_split('/[,\s]+/u', $pattern, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            if ($part !== '' && mb_strpos($userMessageLower, $part) !== false) {
                $score++;
            }
        }

        if ($score > 0) {
            $score += (int)$row['priority'];
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestRow = $row;
        }
    }

    if (!$bestRow) {
        return null;
    }

    $serviceLink = null;
    if (!empty($bestRow['related_service_id'])) {
        $serviceLink = 'services.php?service_id=' . (int)$bestRow['related_service_id'];
    }

    return [
        'answer_text' => $bestRow['answer_text'],
        'service_link' => $serviceLink,
    ];
}

