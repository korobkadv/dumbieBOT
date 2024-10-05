<?php
define('APP_ENV', true);

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



function processMessage($message) {
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';

    // Перевірка типу чату
    $chat_type = $message['chat']['type'];
    $bot_username = 'dumbieBOT';
    
    if ($chat_type === 'group' || $chat_type === 'supergroup') {
        if (strpos($text, '@'.$bot_username) === false) {
            return;
        } else {
            $text = str_replace('@'.$bot_username, '', $text);
        }
    }

    // Обробка команд
    if (strpos($text, "/image") === 0) {
        preloader($chatId, function() use ($chatId) {
            imageCommand($chatId);
            });
    } elseif (strpos($text, "/top_movie") === 0) {
        preloader($chatId, function() use ($chatId) {
            topMovieCommand($chatId);
            });
    } elseif (strpos($text, "/popular_movie") === 0) {
        preloader($chatId, function() use ($chatId) {
            popularMovieCommand($chatId);
            });
    } elseif (strpos($text, "/top_anime") === 0) {
        preloader($chatId, function() use ($chatId) {
            topAnimeCommand($chatId);
            });
    } elseif (strpos($text, "/random_anime") === 0) {
        preloader($chatId, function() use ($chatId) {
            randomAnimeCommand($chatId);
            });
    } elseif (strpos($text, "/quote") === 0) {
        preloader($chatId, function() use ($chatId) {
            quoteCommand($chatId);
            });
    } elseif (strpos($text, "/music_video") === 0) {
        preloader($chatId, function() use ($chatId) {
            musicVideoCommand($chatId);
            });
    } elseif (strpos($text, "/funny_video") === 0) {
        preloader($chatId, function() use ($chatId) {
            funnyVideoCommand($chatId);
            });
    } elseif (strpos($text, "/true_or_false") === 0) {
             trueOrFalseGame($chatId);
    } elseif (strpos($text, "/millionaire") === 0) {
             millionaire($chatId);
    } elseif (strpos($text, "/start") === 0) {
        sendMessage($chatId, 'Привіт!', 'text');
    } else {
        sendMessage($chatId, 'Я Вас не зрозумів!', 'text');
    }
}

// Виклик setMyCommands для встановлення команд
setMyCommands();

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    processMessage($update["message"]);
} elseif (isset($update["callback_query"])) {
    $callbackData = explode('|', $update["callback_query"]['data']);
    $callbackType = $callbackData[0];

    // Перевіряємо тип callback і викликаємо відповідний обробник
    if (in_array($callbackType, ['true', 'false'])) {
        processCallbackQuery($update["callback_query"]); // Обробник для гри "Правда або брехня"
    } else {
        processMillionaireCallbackQuery($update["callback_query"]); // Обробник для гри "Хто хоче стати мільйонером"
    }
}

?>
