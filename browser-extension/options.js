/**
 * Options Page Script для Browser Extension
 */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('settings-form');
  const apiUrlInput = document.getElementById('api-url');
  const apiTokenInput = document.getElementById('api-token');
  const autoParseCheckbox = document.getElementById('auto-parse');
  const statusDiv = document.getElementById('status');
  const getTokenLink = document.getElementById('get-token-link');

  // Загружаем сохраненные настройки
  chrome.storage.sync.get(['apiUrl', 'apiToken', 'autoParse'], (result) => {
    if (result.apiUrl) {
      apiUrlInput.value = result.apiUrl;
    }
    if (result.apiToken) {
      apiTokenInput.value = result.apiToken;
    }
    if (result.autoParse !== undefined) {
      autoParseCheckbox.checked = result.autoParse;
    }

    // Устанавливаем ссылку на получение токена
    const apiUrl = result.apiUrl || 'http://localhost:8000';
    getTokenLink.href = `${apiUrl}/login`;
  });

  // Показываем статус
  function showStatus(message, type = 'success') {
    statusDiv.textContent = message;
    statusDiv.className = `status ${type}`;
    statusDiv.style.display = 'block';
    setTimeout(() => {
      statusDiv.style.display = 'none';
    }, 3000);
  }

  // Сохранение настроек
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const apiUrl = apiUrlInput.value.trim();
    const apiToken = apiTokenInput.value.trim();
    const autoParse = autoParseCheckbox.checked;

    if (!apiUrl || !apiToken) {
      showStatus('Заполните все поля', 'error');
      return;
    }

    // Валидация URL
    try {
      new URL(apiUrl);
    } catch {
      showStatus('Некорректный URL', 'error');
      return;
    }

    // Сохраняем настройки
    chrome.storage.sync.set({
      apiUrl: apiUrl,
      apiToken: apiToken,
      autoParse: autoParse,
    }, () => {
      if (chrome.runtime.lastError) {
        showStatus('Ошибка сохранения: ' + chrome.runtime.lastError.message, 'error');
      } else {
        showStatus('Настройки сохранены!', 'success');
        
        // Обновляем ссылку на получение токена
        getTokenLink.href = `${apiUrl}/login`;
      }
    });
  });
});

