export const startCommand = (bot, chatId) => {
  bot.sendMessage(
    chatId,
    `😉 Привіт! В пункті "Меню", знизу, можете обрати чим я можу Вам допомогти. Або клацніть на зображення "/" знизу`
  );
};
