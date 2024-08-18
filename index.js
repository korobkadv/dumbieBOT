const TelegramApi = require("node-telegram-bot-api");
const allQuotes = require("./db/data.js");
const allMusicVideo = require("./db/musicVideo.json");
const allImages = require("./db/images.json");

const token = "7016873884:AAE_sIUu7_huxaQIkGBsHtculGbM2sobKeA";

const bot = new TelegramApi(token, { polling: true });

bot.setMyCommands([
  { command: "/start", description: "Початкове привітання" },
  { command: "/image", description: "Отримати зображенння" },
  { command: "/quote", description: "Рандомна цитата" },
  { command: "/music_video", description: "Музыкальне відео" },
]);

const start = () => {
  bot.on("message", async (msg) => {
    const text = msg.text;
    const chatId = msg.chat.id;
    const firstName = msg.chat.first_name;

    if (text === "/start") {
      return bot.sendMessage(
        chatId,
        `Привіт ${firstName}! Чим я можу допомогти?`
      );
    }

    if (text === "/image") {
      const randomIndexImages = Math.floor(Math.random() * allImages.length);
      const randomImage = allImages[randomIndexImages];
      return bot.sendPhoto(chatId, randomImage.url);
    }

    if (text === "/music_video") {
      const randomIndexMusicVideo = Math.floor(
        Math.random() * allMusicVideo.length
      );
      const randomMusicVideo = allMusicVideo[randomIndexMusicVideo];
      return bot.sendMessage(chatId, randomMusicVideo.url);
    }

    if (text === "/quote") {
      const randomIndex = Math.floor(Math.random() * allQuotes.length);
      const randomQuotes = allQuotes[randomIndex];

      return bot.sendMessage(
        chatId,
        `${randomQuotes.quote} (${randomQuotes.title})`
      );
    }

    return bot.sendMessage(chatId, "Я Вас не зрозумів!");
  });
};

start();
