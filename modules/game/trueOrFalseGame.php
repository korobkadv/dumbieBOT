<?php

function trueOrFalseGame($chatId) {
    $questionData = getRandomTFQuestion();

    if ($questionData['type'] === 'tf_question_data') {
        $questionText = escapeMarkdownV2($questionData['question']);
        $factId = $questionData['fact_id'];

        // Формуємо текст повідомлення
        $messageText = "*Питання:*\n{$questionText}";

        // Формуємо клавіатуру з правильним callback_data
        $keyboard = [
            'inline_keyboard' => [
                [
                    // Додаємо префікс 'tf_ans|'
                    ['text' => '✔️ Правда', 'callback_data' => 'tf_ans|true|' . $factId],
                    ['text' => '❌ Брехня', 'callback_data' => 'tf_ans|false|' . $factId]
                ]
            ]
        ];

        sendMessage($chatId, $messageText, 'text', $keyboard);
    } else {
        // Надсилаємо повідомлення про помилку (без parse_mode)
        sendMessage($chatId, $questionData['content'], 'text', null, true);
    }
}

function handleCallbackQuery($callbackQuery) {
    $callbackData = explode('|', $callbackQuery['data']);
    $answer = $callbackData[0];
    $factId = $callbackData[1];
    $chatId = $callbackQuery['message']['chat']['id'];
    $userName = $callbackQuery['from']['first_name']; // Отримуємо ім'я користувача

    $fact = getFactById($factId);
    $isCorrect = ($fact['isTrue'] && $answer === 'true') || (!$fact['isTrue'] && $answer === 'false');

    $answerText = 'Брехня!';
    if ($answer === 'true') { $answerText = 'Правда!'; }
    
    // Екрануємо перше повідомлення
    $msg1 = escapeMarkdownV2("$answerText Каже $userName");
    sendMessage($chatId, $msg1, 'text');

    // Екрануємо опис факту
    $escapedDescription = escapeMarkdownV2($fact['description']);
    
    if ($isCorrect) {
        // Екрануємо друге повідомлення (успіх)
        $msg2_success = escapeMarkdownV2("😆👍 $userName правий! ") . $escapedDescription;
        sendMessage($chatId, $msg2_success, 'text');
    } else {
         // Екрануємо друге повідомлення (помилка)
        $msg2_fail = escapeMarkdownV2("😝👎 $userName помиляється! ") . $escapedDescription;
        sendMessage($chatId, $msg2_fail, 'text');
    }
}

// Функція для отримання рандомного факту з JSON
function getRandomFact() {
    $facts = json_decode(file_get_contents('./data/trueOrFalseGame.json'), true);
    $index = rand(0, count($facts) - 1);
    return $facts[$index];
}

// Функція для отримання факту за його ID
function getFactById($id) {
    $facts = json_decode(file_get_contents('./data/trueOrFalseGame.json'), true);
    foreach ($facts as $fact) {
        if ($fact['id'] == $id) {
            return $fact;
        }
    }
    return null;
}

/**
 * Отримує випадковий факт/питання з ID для API.
 *
 * @return array Масив з ['type' => 'tf_question_data', 'question' => string, 'fact_id' => int] або ['type' => 'error', 'content' => string]
 */
function getRandomTFQuestion() {
    $filePath = './data/trueOrFalseGame.json';
    if (!file_exists($filePath)) {
        error_log("getRandomTFQuestion: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл гри не знайдено.'];
    }

    $data = @file_get_contents($filePath); // @ для придушення попередження, якщо файл недоступний
    if ($data === false) {
        error_log("getRandomTFQuestion: Failed to read file: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка читання файлу гри.'];
    }

    $facts = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("getRandomTFQuestion: Failed to decode JSON from " . $filePath . " Error: " . json_last_error_msg());
        return ['type' => 'error', 'content' => '❗ Помилка обробки файлу гри.'];
    }

    if (empty($facts) || !is_array($facts)) {
        error_log("getRandomTFQuestion: No facts found or data is not an array in " . $filePath);
        return ['type' => 'error', 'content' => '❗ У файлі гри немає фактів або формат невірний.'];
    }

    try {
        $index = random_int(0, count($facts) - 1);
        $fact = $facts[$index];

        // Перевіряємо наявність необхідних ключів
        if (!isset($fact['id']) || !isset($fact['fact'])) {
            error_log("getRandomTFQuestion: Keys 'id' or 'fact' not found for index " . $index . " in " . $filePath);
            return ['type' => 'error', 'content' => '❗ Помилка даних: відсутній ID або текст факту.'];
        }

        return [
            'type' => 'tf_question_data',
            'question' => $fact['fact'],
            'fact_id' => $fact['id']
        ];
    } catch (Exception $e) {
        error_log("getRandomTFQuestion: Error selecting random fact: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Помилка вибору випадкового факту.'];
    }
}

/**
 * Перевіряє відповідь користувача на факт.
 *
 * @param int $factId ID факту.
 * @param bool $userAnswerBool Відповідь користувача (true/false).
 * @return array Масив з ['type' => 'tf_result', 'correct' => bool, 'description' => string] або ['type' => 'error', 'content' => string]
 */
function checkTFAnswer($factId, $userAnswerBool) {
    $filePath = './data/trueOrFalseGame.json';
     if (!file_exists($filePath)) {
        error_log("checkTFAnswer: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл гри не знайдено.'];
    }
    
    // Використовуємо існуючу функцію для отримання факту
    $fact = getFactById($factId); 

    if ($fact === null) {
        error_log("checkTFAnswer: Fact with ID " . $factId . " not found.");
        return ['type' => 'error', 'content' => '❗ Помилка: Факт не знайдено. Можливо, він був змінений.'];
    }
    
    // Перевіряємо наявність isTrue та description
    if (!isset($fact['isTrue']) || !isset($fact['description'])) {
        error_log("checkTFAnswer: Keys 'isTrue' or 'description' not found for fact ID " . $factId);
        return ['type' => 'error', 'content' => '❗ Помилка даних: відсутня відповідь або пояснення для факту.'];
    }

    $correctAnswerBool = (bool)$fact['isTrue'];
    $isCorrect = ($userAnswerBool === $correctAnswerBool);

    return [
        'type' => 'tf_result',
        'correct' => $isCorrect,
        'description' => $fact['description']
    ];
}

?>
