<?php

function millionaire($chatId) {
    $question = getRandomQuestion();

    // Створюємо кнопки для відповідей
    $keyboard = [
        'inline_keyboard' => array_map(function($option) use ($question) {
            return [
                ['text' => $option, 'callback_data' => 'm_ans|' . $option . '|' . $question['id']]
            ];
        }, $question['answerOptions'])
    ];

    // Відправляємо питання користувачеві
    sendMessage($chatId, $question['question'], 'text', $keyboard);
}

function handleMillionaireCallbackQuery($callbackQuery) {
    // Розбираємо callback_data з урахуванням нового формату
    $callbackParts = explode('|', $callbackQuery['data'], 3); // Ліміт 3 частини
    if (count($callbackParts) !== 3 || $callbackParts[0] !== 'm_ans') {
        error_log("handleMillionaireCallbackQuery: Invalid callback data format: " . $callbackQuery['data']);
        // Можливо, відповісти користувачеві про помилку?
        return; 
    }
    
    $answer = $callbackParts[1];
    $questionId = $callbackParts[2];
    $chatId = $callbackQuery['message']['chat']['id'];
    $userName = $callbackQuery['from']['first_name']; // Отримуємо ім'я користувача

    $question = getQuestionById($questionId);
    $isCorrect = ($question['isTrue'] === $answer);

    // Екрануємо компоненти відповіді ОКРЕМО
    $escapedAnswer = escapeMarkdownV2($answer);
    $escapedUserName = escapeMarkdownV2($userName);
    $escapedPrice = escapeMarkdownV2($question['price']);

    if ($isCorrect) {
        // Екрануємо статичні частини і збираємо повідомлення
        $part1 = escapeMarkdownV2("🎉😆👍 ");
        $part2 = escapeMarkdownV2(", відповідає ");
        $part3 = escapeMarkdownV2(". Це правильна відповідь! Ти отримуєш ");
        $part4 = escapeMarkdownV2(" балів!");
        $escapedResponseMessage = $part1 . $escapedAnswer . $part2 . $escapedUserName . $part3 . $escapedPrice . $part4;
    } else {
        // Екрануємо статичні частини і збираємо повідомлення
        $part1 = escapeMarkdownV2("😕👎 ");
        $part2 = escapeMarkdownV2(", відповідає ");
        $part3 = escapeMarkdownV2(". Це помилка! Ти втрачаєш ");
        $part4 = escapeMarkdownV2(" балів!");
        $escapedResponseMessage = $part1 . $escapedAnswer . $part2 . $escapedUserName . $part3 . $escapedPrice . $part4;
    }
    
    // Відправляємо результат користувачеві
    sendMessage($chatId, $escapedResponseMessage, 'text');
}

// Функція для отримання випадкового питання з JSON
function getRandomQuestion() {
    $questions = json_decode(file_get_contents('./data/millionaire.json'), true);
    $index = rand(0, count($questions) - 1);
    return $questions[$index];
}

// Функція для отримання питання за його ID
function getQuestionById($id) {
    $questions = json_decode(file_get_contents('./data/millionaire.json'), true);
    foreach ($questions as $question) {
        if ($question['id'] == $id) {
            return $question;
        }
    }
    return null;
}

// Обробка callback запиту
function processMillionaireCallbackQuery($callbackQuery) {
    handleMillionaireCallbackQuery($callbackQuery);
}

/**
 * Отримує випадкове питання мільйонера з варіантами та ID для API.
 *
 * @return array Масив з ['type' => 'millionaire_question_data', 'question' => ..., 'options' => [...], 'question_id' => int] 
 *               або ['type' => 'error', 'content' => string]
 */
function getRandomMillionaireQuestion() {
    $filePath = './data/millionaire.json';
    if (!file_exists($filePath)) {
        error_log("getRandomMillionaireQuestion: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл гри Мільйонер не знайдено.'];
    }

    $data = @file_get_contents($filePath);
    if ($data === false) {
        error_log("getRandomMillionaireQuestion: Failed to read file: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка читання файлу гри Мільйонер.'];
    }

    $questions = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("getRandomMillionaireQuestion: Failed to decode JSON from " . $filePath . " Error: " . json_last_error_msg());
        return ['type' => 'error', 'content' => '❗ Помилка обробки файлу гри Мільйонер.'];
    }

    if (empty($questions) || !is_array($questions)) {
        error_log("getRandomMillionaireQuestion: No questions found or data is not an array in " . $filePath);
        return ['type' => 'error', 'content' => '❗ У файлі гри Мільйонер немає питань або формат невірний.'];
    }

    try {
        $index = random_int(0, count($questions) - 1);
        $questionData = $questions[$index];

        // Перевіряємо наявність необхідних ключів
        if (!isset($questionData['id']) || !isset($questionData['question']) || !isset($questionData['answerOptions']) || !is_array($questionData['answerOptions'])) {
            error_log("getRandomMillionaireQuestion: Required keys ('id', 'question', 'answerOptions') not found or invalid for index " . $index . " in " . $filePath);
            return ['type' => 'error', 'content' => '❗ Помилка даних: відсутній ID, питання або варіанти.'];
        }

        return [
            'type' => 'millionaire_question_data',
            'question_id' => $questionData['id'],
            'question' => $questionData['question'],
            'options' => $questionData['answerOptions']
        ];
    } catch (Exception $e) {
        error_log("getRandomMillionaireQuestion: Error selecting random question: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Помилка вибору випадкового питання.'];
    }
}

/**
 * Перевіряє відповідь користувача на питання Мільйонера.
 *
 * @param int $questionId ID питання.
 * @param string $userAnswerText Текст відповіді користувача.
 * @return array Масив з ['type' => 'millionaire_result', 'correct' => bool, 'correct_answer' => string, 'price' => string] 
 *               або ['type' => 'error', 'content' => string]
 */
function checkMillionaireAnswer($questionId, $userAnswerText) {
    $filePath = './data/millionaire.json';
     if (!file_exists($filePath)) {
        error_log("checkMillionaireAnswer: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл гри Мільйонер не знайдено.'];
    }
    
    $question = getQuestionById($questionId);

    if ($question === null) {
        error_log("checkMillionaireAnswer: Question with ID " . $questionId . " not found.");
        return ['type' => 'error', 'content' => '❗ Помилка: Питання не знайдено. Можливо, воно було змінене.'];
    }
    
    // Перевіряємо наявність isTrue та price
    if (!isset($question['isTrue']) || !isset($question['price'])) {
        error_log("checkMillionaireAnswer: Keys 'isTrue' or 'price' not found for question ID " . $questionId);
        return ['type' => 'error', 'content' => '❗ Помилка даних: відсутня відповідь або ціна питання.'];
    }

    $correctAnswerText = $question['isTrue'];
    $isCorrect = ($userAnswerText === $correctAnswerText);
    $price = $question['price'];

    return [
        'type' => 'millionaire_result',
        'correct' => $isCorrect,
        'correct_answer' => $correctAnswerText,
        'price' => $price
    ];
}

?>
