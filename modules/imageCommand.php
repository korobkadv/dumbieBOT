<?php
function imageCommand($chatId = null) {
    $pixabayKey = PIXABAY_KEY;

    $page = randomIndex(1, 15);
    $imageURL = "https://pixabay.com/api/?key=$pixabayKey&editors_choice=true&pretty=true&page=$page";

    $response = file_get_contents($imageURL);
    $data = json_decode($response, true);

    if (isset($data['hits']) && count($data['hits']) > 0) {
        $imageIndex = randomIndex(0, count($data['hits']) - 1);
        $imageUrl = $data['hits'][$imageIndex]['largeImageURL'];

        // Перевіряємо, звідки надійшов запит
        if ($chatId) {
            sendMessage($chatId, $imageUrl, 'photo');
        } else {
            echo json_encode([
                'status' => 'success',
                'imageUrl' => $imageUrl
            ]);
        }
    } else {
        $errorMessage = "😅 Вибачте, на сайті з зображеннями сталася помилка! Спробуйте ще раз.";
        if ($chatId) {
            sendMessage($chatId, $errorMessage);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $errorMessage
            ]);
        }
    }
}

// Обробка запиту
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request = json_decode(file_get_contents('php://input'), true);
    
    if (isset($request['command']) && $request['command'] === '/image') {
        define('APP_ENV', true);
        require_once '../config.php';
        require_once '../helpers/randomIndex.php';
        imageCommand();
    }
} elseif (isset($_POST['chat_id'])) {
    $chatId = $_POST['chat_id'];
    imageCommand($chatId);
}
?>
