export const startCommand = (bot, chatId, firstName) => {
  bot.sendMessage(
    chatId,
    `😉 Привіт ${firstName}! В пункті меню, знизу, можете обрати чим я можу Вам допомогти. Або клацніть на команду: /help.`
  );
};
