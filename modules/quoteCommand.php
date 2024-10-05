<?php
function quoteCommand($chatId) {
    // Завантаження даних із JSON-файлу
    $data = file_get_contents('./data/quote.json');
    $allQuotes = json_decode($data, true);

    // Випадковий вибір цитати
    $randomQuoteIndex = randomIndex(0, count($allQuotes));
    $quote = $allQuotes[$randomQuoteIndex]['quote'];
    $title = $allQuotes[$randomQuoteIndex]['title'];

    // Відправка повідомлення
    $messageText = "$quote ($title)";
    sendMessage($chatId, $messageText, 'text');
}
?>