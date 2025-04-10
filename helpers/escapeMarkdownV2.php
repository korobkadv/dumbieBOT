<?php
/**
 * Екранує спеціальні символи для Telegram MarkdownV2.
 *
 * @param string $text Текст для екранування.
 * @return string Екранований текст.
 */
function escapeMarkdownV2($text) {
    // Повертаємо повну версію з preg_replace_callback та модифікатором 'u'
    return preg_replace_callback(
        '/([\_*`\[\]()~>#+=|{}.!-])/u', // Повний набір символів
        function ($matches) {
            return '\\' . $matches[1]; // Екрануємо слешем
        },
        $text
    );
}
?> 