<?php
require_once './config.php';
require_once './modules/commands.php';
require_once './helpers/randomIndex.php';
require_once './modules/sendMessage.php';
require_once './modules/preloader.php';
require_once './modules/imageCommand.php';
require_once './modules/topMoviesCommand.php';
require_once './modules/popularMoviesCommand.php';
require_once './modules/topAnimeCommand.php';
require_once './modules/randomAnimeCommand.php';
require_once './modules/quoteCommand.php';
require_once './modules/musicVideoCommand.php';
require_once './modules/funnyVideoCommand.php';
require_once './modules/game/trueOrFalseGame.php';
require_once './modules/game/millionaire.php';

function handleCommand($command, $chatId) {
    error_log("Command: $command");
    ob_start();
    if ($command === '/image') {
        echo imageCommand($chatId);
    } elseif ($command === '/top_movie') {
        echo topMovieCommand($chatId);
    } elseif ($command === '/popular_movie') {
        echo popularMovieCommand($chatId);
    } elseif ($command === '/top_anime') {
        echo topAnimeCommand($chatId);
    } elseif ($command === '/random_anime') {
        echo randomAnimeCommand($chatId);
    } elseif ($command === '/quote') {
        echo quoteCommand($chatId);
    } elseif ($command === '/music_video') {
        echo musicVideoCommand($chatId);
    } elseif ($command === '/funny_video') {
        echo funnyVideoCommand($chatId);
    } elseif ($command === '/true_or_false') {
        echo trueOrFalseGame($chatId);
    } elseif ($command === '/millionaire') {
        echo millionaire($chatId);
    } else {
        echo json_encode(['type' => 'error', 'content' => "Unknown command!"]);
    }
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $command = $data['command'] ?? '';
    $chatId = $data['chat_id'] ?? 12345; // Використовуйте будь-який тестовий chatId

    // Обробка команди
    $result = handleCommand($command, $chatId);

    // Логування результату для відладки
    

    // Повертаємо результат у форматі JSON
    header('Content-Type: application/json');
    echo $result;
} else {
    // Якщо запит не POST, повертаємо помилку
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method.']);
}