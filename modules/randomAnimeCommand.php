<?php
function randomAnimeCommand($chatId) {
    $animeURL = "https://api.jikan.moe/v4/random/anime";

    $options = [
        'http' => [
            'method' => 'GET',
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($animeURL, false, $context);
    $data = json_decode($response, true);

    if (isset($data['data']) && count($data['data']) > 0) {
        $anime = $data['data'];

        $animeScore = $anime['score'];
        if ($animeScore === null) {
            $animeScore = 'Рейтинг відсутній';
        } 
        // Отримуємо лише рік з дати випуску
        $year = substr($anime['aired']['from'], 0, 4);

        // Відправляємо повідомлення з назвою аніме та рейтингом
        $messageText = "{$anime['title']} ({$year}) \n⭐ {$animeScore}";
        sendMessage($chatId, $messageText, 'text');

        // Відправляємо постер аніме
        $imageUrl = $anime['images']['jpg']['image_url'];
        sendMessage($chatId, $imageUrl, 'photo');
    } else {
        sendMessage($chatId, "❗ Вибачте, сталася помилка. Спробуйте ще раз.", 'text');
    }
}
?>
