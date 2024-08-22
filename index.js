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

// Підключаєм бот з допомогою токєна
const { TELEGRAM_TOKEN } = process.env;
const bot = new TelegramApi(TELEGRAM_TOKEN);

// Використовуйте цю функцію замість console.log
const sendLogToChat = (bot, chatId, message) => {
  bot.sendMessage(chatId, `LOG: ${message}`);
};

// Відображення команд для користувача
bot.setMyCommands(commands);

// Запускаємо Express сервер
const app = express();
app.use(express.json());

// Обробка запитів від Telegram
app.post(`/bot${TELEGRAM_TOKEN}`, (req, res) => {
  const { message } = req.body;

  if (message) {
    const text = message.text;
    const chatId = message.chat.id;
    const firstName = message.chat.first_name;

    sendLogToChat(bot, chatId, message);

    const chatType = message.chat.type;
    if (chatType === 'group' || chatType === 'supergroup') {
      if (text.includes('@dumbieBOT')) {
        text = text.replace('@dumbieBOT', '').trim();
      }
    }

    switch (text) {
      case '/start':
        return preloader(bot, chatId, () =>
          startCommand(bot, chatId, firstName)
        );

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
  }

  res.sendStatus(200); // Обов'язково відповідайте 200 OK
});

// Фейковий HTTP-сервер для перевірки статусу бота
app.get('/', (req, res) => {
  res.send('Bot is running');
});

// Запуск сервера
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
