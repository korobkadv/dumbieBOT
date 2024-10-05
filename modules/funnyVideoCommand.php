<?php
function funnyVideoCommand($chatId) {
    // Завантаження даних із JSON-файлу
    $data = file_get_contents('./data/funnyVideo.json');
    $allFunnyVideo = json_decode($data, true);

    // Випадковий вибір цитати
    $randomVideoIndex = randomIndex(0, count($allFunnyVideo));
    $urlVideo = $allFunnyVideo[$randomVideoIndex]['url'];

    // Відправка повідомлення
    sendMessage($chatId, $urlVideo, 'video');
}
?>