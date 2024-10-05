<?php

function trueOrFalseGame($chatId) {
    $fact = getRandomFact();

    // Створюємо кнопки для відповіді
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'Правда', 'callback_data' => 'true|' . $fact['id']],
                ['text' => 'Брехня', 'callback_data' => 'false|' . $fact['id']],
            ]
        ]
    ];

    // Відправляємо факт користувачеві
    sendMessage($chatId, $fact['fact'], 'text', $keyboard);
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
    sendMessage($chatId, "$answerText Каже $userName", 'text');

    if ($isCorrect) {
        sendMessage($chatId, "😆👍 $userName правий! " . $fact['description'], 'text');
    } else {
        sendMessage($chatId, "😝👎 $userName помиляється! " . $fact['description'], 'text');
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

// Обробка callback запиту
function processCallbackQuery($callbackQuery) {
    handleCallbackQuery($callbackQuery);
}

?>
