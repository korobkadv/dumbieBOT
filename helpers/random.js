export const randomIndex = (maxNumber, isPage = 'no') => {
  let random = Math.floor(Math.random() * maxNumber);
  if ((random === 0) & (isPage === 'yes')) {
    random += 1;
  }
  return random;
};
