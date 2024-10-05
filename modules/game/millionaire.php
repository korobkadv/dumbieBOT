<?php

function millionaire($chatId) {
    $question = getRandomQuestion();

    // Створюємо кнопки для відповідей
    $keyboard = [
        'inline_keyboard' => array_map(function($option) use ($question) {
            return [
                ['text' => $option, 'callback_data' => $option . '|' . $question['id']]
            ];
        }, $question['answerOptions'])
    ];

    // Відправляємо питання користувачеві
    sendMessage($chatId, $question['question'], 'text', $keyboard);
}

function handleMillionaireCallbackQuery($callbackQuery) {
    $callbackData = explode('|', $callbackQuery['data']);
    $answer = $callbackData[0];
    $questionId = $callbackData[1];
    $chatId = $callbackQuery['message']['chat']['id'];
    $userName = $callbackQuery['from']['first_name']; // Отримуємо ім'я користувача

    $question = getQuestionById($questionId);
    $isCorrect = ($question['isTrue'] === $answer);

    $responseMessage = $isCorrect 
        ? "🎉😆👍 $answer, відповідає $userName. Це правильна відповідь! Ти отримуєш {$question['price']} балів!"
        : "😕👎 $answer, відповідає $userName. Це помилка! Ти втрачаєш {$question['price']} балів!";

    // Відправляємо результат користувачеві
    sendMessage($chatId, $responseMessage, 'text');
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

?>
