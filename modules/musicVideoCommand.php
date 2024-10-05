<?php
function musicVideoCommand($chatId) {
    // Завантаження даних із JSON-файлу
    $data = file_get_contents('./data/musicVideo.json');
    $allMusicVideo = json_decode($data, true);

    // Випадковий вибір цитати
    $randomVideoIndex = randomIndex(0, count($allMusicVideo));
    $urlVideo = $allMusicVideo[$randomVideoIndex]['url'];

    // Відправка повідомлення
    sendMessage($chatId, $urlVideo, 'text');
}
?>