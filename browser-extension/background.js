/**
 * Background Service Worker для Browser Extension
 * Обрабатывает события и управляет состоянием расширения
 */

// Inline SVG (data URL) чтобы не зависеть от png-иконок в репо
const ICON_DATA_URL =
  'data:image/svg+xml;charset=utf-8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
      <rect width="96" height="96" rx="18" fill="#3B82F6"/>
      <path d="M24 50c0-12 10-22 22-22h26v10H46c-6 0-12 6-12 12s6 12 12 12h14v10H46c-12 0-22-10-22-22z" fill="#fff"/>
      <path d="M72 46c0 12-10 22-22 22H24V58h26c6 0 12-6 12-12s-6-12-12-12H36V24h14c12 0 22 10 22 22z" fill="#E5E7EB" opacity="0.8"/>
    </svg>`
  );

// Обработка установки расширения
chrome.runtime.onInstalled.addListener((details) => {
  if (details.reason === 'install') {
    // Первая установка - открываем страницу настроек
    chrome.runtime.openOptionsPage();
  }
});

// Обработка сообщений от content script
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.type === 'price-sent') {
    // Показываем уведомление
    try {
      chrome.notifications.create({
        type: 'basic',
        iconUrl: ICON_DATA_URL,
        title: 'EMIGRAM MARKET',
        message: `Цена отправлена: ${request.data.price} ${request.data.currency}`,
      });
    } catch (e) {
      // Если permission отключен/браузер ограничивает — просто игнорируем
      console.warn('EMIGRAM: notifications unavailable', e);
    }
  }
});

// Обработка клика по иконке расширения
chrome.action.onClicked.addListener((tab) => {
  // Открываем popup (если не настроен) или выполняем действие
  chrome.tabs.sendMessage(tab.id, { action: 'parse-price' });
});

