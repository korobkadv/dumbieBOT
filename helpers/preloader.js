export const preloader = async (bot, chatId, executeCommand) => {
  // 1. Відправляємо повідомлення про завантаження
  const loadingMessage = await bot.sendMessage(
    chatId,
    '⌛ Зачекайте, ваш запит виконується...'
  );

  try {
    // 2. Виконуємо основну команду
    await executeCommand();

    // 3. Оновлюємо повідомлення з результатом
    await bot.deleteMessage(chatId, loadingMessage.message_id);
  } catch (error) {
    // У разі помилки оновлюємо повідомлення відповідним текстом
    await bot.editMessageText('😅 Виникла помилка', {
      chat_id: chatId,
      message_id: loadingMessage.message_id,
    });
  }
};
