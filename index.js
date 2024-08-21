import express from 'express';
import TelegramApi from 'node-telegram-bot-api';
import 'dotenv/config';
import {
  commands,
  startCommand,
  helpCommand,
  imageCommand,
  funnyVideoCommand,
  musicVideoCommand,
  quoteCommand,
  topAnimeCommand,
  randomAnimeCommand,
  topMovieCommand,
  popularMovieCommand,
} from './modules/allModules.js';
import { preloader } from './helpers/preloader.js';

//Підключаєм бот з допомогою токєна
const { TELEGRAM_TOKEN } = process.env;
const bot = new TelegramApi(TELEGRAM_TOKEN, { polling: true });

//Відображення команд для користувача
bot.setMyCommands(commands);

//Головна функція
const start = () => {
  bot.on('message', async msg => {
    const text = msg.text;
    const chatId = msg.chat.id;
    const firstName = msg.chat.first_name;

    const chatType = msg.chat.type;
    if (chatType === 'group' || chatType === 'supergroup') {
      if (!text.includes('@dumbieBOT')) {
        return; // Ігнорувати повідомлення, які не адресовані боту
      }
    }

    switch (text) {
      case '/start':
        return preloader(bot, chatId, () =>
          startCommand(bot, chatId, firstName)
        );

      case '/help':
        return preloader(bot, chatId, () => helpCommand(bot, chatId));

      case '/image':
        return preloader(bot, chatId, () => imageCommand(bot, chatId));

      case '/funny_video':
        return preloader(bot, chatId, () => funnyVideoCommand(bot, chatId));

      case '/music_video':
        return preloader(bot, chatId, () => musicVideoCommand(bot, chatId));

      case '/quote':
        return preloader(bot, chatId, () => quoteCommand(bot, chatId));

      case '/top_anime':
        return preloader(bot, chatId, () => topAnimeCommand(bot, chatId));

      case '/random_anime':
        return preloader(bot, chatId, () => randomAnimeCommand(bot, chatId));

      case '/top_movie':
        return preloader(bot, chatId, () => topMovieCommand(bot, chatId));

      case '/popular_movie':
        return preloader(bot, chatId, () => popularMovieCommand(bot, chatId));

      default:
        return bot.sendMessage(chatId, 'Я Вас не зрозумів!');
    }
  });
};

start();

// Запустити фейковий HTTP-сервер
const app = express();
const PORT = process.env.PORT || 3000;
app.get('/', (req, res) => {
  res.send('Bot is running');
});
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
