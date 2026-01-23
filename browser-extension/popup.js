/**
 * Popup Script для Browser Extension
 */

document.addEventListener('DOMContentLoaded', () => {
  const parseBtn = document.getElementById('parse-btn');
  const optionsBtn = document.getElementById('options-btn');
  const statusDiv = document.getElementById('status');
  const productInfo = document.getElementById('product-info');
  const productName = document.getElementById('product-name');
  const productUrl = document.getElementById('product-url');
  const productPrice = document.getElementById('product-price');
  const productCurrency = document.getElementById('product-currency');
  const openDashboard = document.getElementById('open-dashboard');

  // Загружаем настройки
  chrome.storage.sync.get(['apiUrl'], (result) => {
    const apiUrl = result.apiUrl || 'http://localhost:8000';
    openDashboard.href = apiUrl;
    openDashboard.target = '_blank';
  });

  // Показываем статус
  function showStatus(message, type = 'info') {
    statusDiv.textContent = message;
    statusDiv.className = `status ${type}`;
    statusDiv.style.display = 'block';
    setTimeout(() => {
      statusDiv.style.display = 'none';
    }, 5000);
  }

  // Загружаем данные о товаре со страницы
  async function loadProductData() {
    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

      const data = await new Promise((resolve) => {
        chrome.tabs.sendMessage(tab.id, { action: 'get-product-data' }, (response) => {
          if (chrome.runtime.lastError) {
            resolve(null);
            return;
          }
          resolve(response?.data || null);
        });
      });
      
      if (data && data.price) {
        productName.textContent = data.name || 'Товар';
        productUrl.textContent = data.url;
        productPrice.textContent = `${data.price} ${data.currency}`;
        productCurrency.textContent = `Магазин: ${data.domain}`;
        productInfo.style.display = 'block';
        return data;
      } else {
        showStatus('Цена не найдена на странице', 'error');
        productInfo.style.display = 'none';
        return null;
      }
    } catch (error) {
      console.error('Failed to load product data:', error);
      showStatus('Ошибка загрузки данных', 'error');
      return null;
    }
  }

  // Парсинг и отправка цены
  async function parseAndSend() {
    parseBtn.disabled = true;
    parseBtn.textContent = 'Парсинг...';

    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      
      // Отправляем сообщение в content script
      chrome.tabs.sendMessage(tab.id, { action: 'parse-price' }, (response) => {
        if (chrome.runtime.lastError) {
          showStatus('Ошибка: ' + chrome.runtime.lastError.message, 'error');
          parseBtn.disabled = false;
          parseBtn.textContent = 'Парсить цену';
          return;
        }

        if (response && response.success) {
          showStatus('Цена успешно отправлена!', 'success');
          loadProductData(); // Обновляем данные
        } else {
          showStatus(response?.error || 'Не удалось отправить цену', 'error');
        }

        parseBtn.disabled = false;
        parseBtn.textContent = 'Парсить цену';
      });
    } catch (error) {
      showStatus('Ошибка: ' + error.message, 'error');
      parseBtn.disabled = false;
      parseBtn.textContent = 'Парсить цену';
    }
  }

  // Обработчики событий
  parseBtn.addEventListener('click', parseAndSend);
  optionsBtn.addEventListener('click', () => {
    chrome.runtime.openOptionsPage();
  });

  // Загружаем данные при открытии popup
  loadProductData();
});

