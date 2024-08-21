import { allQuotes } from '../db/data.js';
import { randomIndex } from '../helpers/random.js';

export const quoteCommand = (bot, chatId) => {
  return bot.sendMessage(
    chatId,
    `${allQuotes[randomIndex(allQuotes.length)].quote} (${
      allQuotes[randomIndex(allQuotes.length)].title
    })`
  );
};
