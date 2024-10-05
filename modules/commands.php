<?php
// Функція для встановлення команд бота
function setMyCommands() {
    $telegramToken = BOT_TOKEN;
    $url = API_URL . "setMyCommands";
    $commands = [
        [
            'command' => 'start',
            'description' => 'Початкове привітання'
        ],
        [
            'command' => 'true_or_false',
            'description' => 'Гра правда або брехня'
        ],
        [
            'command' => 'millionaire',
            'description' => 'Гра хто хоче стати мільйонером'
        ],
        [
            'command' => 'image',
            'description' => 'Рандомне зображенння'
        ],
        [
            'command' => 'top_movie',
            'description' => 'Кіно з високим рейтингом'
        ],
        [
            'command' => 'popular_movie',
            'description' => 'Популярне з нового кіно'
        ],
        [
            'command' => 'top_anime',
            'description' => 'Аніме з високим рейтингом'
        ],
        [
            'command' => 'random_anime',
            'description' => 'Рандомне аніме з будь яким рейтингом'
        ],
        [
            'command' => 'quote',
            'description' => 'Цитата з фільмів, аніме та від відомих людей'
        ],
        [
            'command' => 'music_video',
            'description' => 'Музичне відео'
        ],
        [
            'command' => 'funny_video',
            'description' => 'Кумедне відео'
        ],
    ];

    $postData = [
        'commands' => json_encode($commands),
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];
    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
?>