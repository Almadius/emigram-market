/**
 * Content Script для парсинга цен с страниц магазинов
 * Работает на всех страницах и извлекает информацию о товаре
 */

(function() {
  'use strict';

  // Fallback selectors (если конфиг магазина не задан в БД или недоступен)
  const DEFAULT_SELECTORS = {
    price: [
      '[data-price]',
      '.price',
      '.product-price',
      '.price-current',
      '[class*="price"]',
      '[id*="price"]',
    ],
    currency: [
      '[data-currency]',
      '.currency',
      '[class*="currency"]',
      '[itemprop="priceCurrency"]',
    ],
    name: [
      'h1',
      '.product-title',
      '[data-product-name]',
      '[class*="title"]',
      '[class*="name"]',
      '[itemprop="name"]',
    ],
  };

  const PARSING_CONFIG_TTL_MS = 5 * 60 * 1000; // 5 минут
  const parsingConfigMemoryCache = new Map(); // domain -> { selectors, expiresAt, etag }

  /**
   * Извлекает цену из текста
   */
  function parsePrice(text) {
    if (!text) return null;
    
    // Удаляем все кроме цифр, точек и запятых
    const cleaned = text.replace(/[^\d.,]/g, '');
    // Заменяем запятую на точку
    const normalized = cleaned.replace(',', '.');
    const price = parseFloat(normalized);
    
    return isNaN(price) || price <= 0 ? null : price;
  }

  /**
   * Извлекает валюту из текста
   */
  function parseCurrency(text) {
    if (!text) return null;
    
    const currencyMap = {
      '€': 'EUR',
      '$': 'USD',
      '£': 'GBP',
      '₽': 'RUB',
      'EUR': 'EUR',
      'USD': 'USD',
      'GBP': 'GBP',
      'RUB': 'RUB',
    };

    const upperText = text.toUpperCase();
    
    for (const [symbol, code] of Object.entries(currencyMap)) {
      if (upperText.includes(symbol) || upperText.includes(code)) {
        return code;
      }
    }

    return 'EUR'; // Default
  }

  /**
   * Ищет элемент по селекторам
   */
  function findElement(selectors) {
    for (const selector of selectors) {
      try {
        const element = document.querySelector(selector);
        if (element) {
          return element;
        }
      } catch (e) {
        // Invalid selector, continue
      }
    }
    return null;
  }

  /**
   * Получает parsing selectors для домена из API (shops.parsing_selectors),
   * кэширует в памяти и в chrome.storage.local.
   */
  async function getSelectorsForDomain(shopDomain) {
    const domain = (shopDomain || '').toLowerCase().trim();
    const now = Date.now();

    const mem = parsingConfigMemoryCache.get(domain);
    if (mem && mem.expiresAt > now && mem.selectors) {
      return mem.selectors;
    }

    const localKey = `parsingConfig:${domain}`;
    try {
      const local = await chrome.storage.local.get([localKey]);
      const cached = local?.[localKey];
      if (cached && cached.expiresAt > now && cached.selectors) {
        parsingConfigMemoryCache.set(domain, cached);
        return cached.selectors;
      }
    } catch (e) {
      // ignore local storage errors
    }

    // Settings from sync
    const { apiToken, apiUrl } = await chrome.storage.sync.get(['apiToken', 'apiUrl']);
    const token = apiToken;
    const baseUrl = (apiUrl || 'http://localhost:8000').replace(/\/+$/, '');

    if (!token) {
      const fallback = normalizeSelectors(DEFAULT_SELECTORS);
      parsingConfigMemoryCache.set(domain, { selectors: fallback, expiresAt: now + PARSING_CONFIG_TTL_MS, etag: null });
      return fallback;
    }

    let etag = mem?.etag || null;
    try {
      const local = await chrome.storage.local.get([localKey]);
      etag = local?.[localKey]?.etag || etag;
    } catch (e) {}

    try {
      const headers = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      };
      if (etag) {
        headers['If-None-Match'] = etag;
      }

      const res = await fetch(`${baseUrl}/api/v1/shops/${encodeURIComponent(domain)}/parsing-config`, {
        method: 'GET',
        headers,
      });

      if (res.status === 304) {
        // use stale local (even if expired) if present
        try {
          const local = await chrome.storage.local.get([localKey]);
          const cached = local?.[localKey];
          if (cached?.selectors) {
            const refreshed = { ...cached, expiresAt: now + PARSING_CONFIG_TTL_MS };
            parsingConfigMemoryCache.set(domain, refreshed);
            await chrome.storage.local.set({ [localKey]: refreshed });
            return refreshed.selectors;
          }
        } catch (e) {}
      }

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const payload = await res.json();
      const selectors = normalizeSelectors(payload?.selectors || DEFAULT_SELECTORS);
      const newEtag = res.headers.get('ETag') || null;

      const record = {
        selectors,
        etag: newEtag,
        expiresAt: now + PARSING_CONFIG_TTL_MS,
      };

      parsingConfigMemoryCache.set(domain, record);
      try {
        await chrome.storage.local.set({ [localKey]: record });
      } catch (e) {}

      return selectors;
    } catch (e) {
      const fallback = normalizeSelectors(DEFAULT_SELECTORS);
      parsingConfigMemoryCache.set(domain, { selectors: fallback, expiresAt: now + PARSING_CONFIG_TTL_MS, etag: null });
      return fallback;
    }
  }

  function normalizeSelectors(selectors) {
    const out = { price: [], currency: [], name: [] };
    const src = selectors && typeof selectors === 'object' ? selectors : {};
    for (const key of ['price', 'currency', 'name']) {
      const v = src[key];
      if (typeof v === 'string' && v.trim()) out[key] = [v.trim()];
      else if (Array.isArray(v)) out[key] = v.map(s => (typeof s === 'string' ? s.trim() : '')).filter(Boolean);
      else out[key] = [];
    }
    // fallback if empty
    if (out.price.length === 0) out.price = DEFAULT_SELECTORS.price.slice();
    if (out.currency.length === 0) out.currency = DEFAULT_SELECTORS.currency.slice();
    if (out.name.length === 0) out.name = DEFAULT_SELECTORS.name.slice();
    return out;
  }

  /**
   * Извлекает данные о товаре со страницы
   */
  async function extractProductData() {
    const shopDomain = window.location.hostname;
    const productUrl = window.location.href;
    const selectors = await getSelectorsForDomain(shopDomain);

    // Извлекаем цену
    let price = null;
    const priceElement = findElement(selectors.price);
    if (priceElement) {
      price = parsePrice(priceElement.textContent || priceElement.innerText);
    }

    // Извлекаем валюту
    let currency = 'EUR';
    const currencyElement = findElement(selectors.currency);
    if (currencyElement) {
      currency = parseCurrency(currencyElement.textContent || currencyElement.innerText);
    } else if (priceElement) {
      // Пытаемся извлечь валюту из элемента с ценой
      currency = parseCurrency(priceElement.textContent || priceElement.innerText);
    }

    // Извлекаем название товара
    let productName = null;
    const nameElement = findElement(selectors.name);
    if (nameElement) {
      productName = (nameElement.textContent || nameElement.innerText).trim();
    }

    return {
      shop_domain: shopDomain,
      product_url: productUrl,
      price: price,
      currency: currency,
      product_name: productName,
    };
  }

  async function extractProductDataWithElements() {
    const shopDomain = window.location.hostname;
    const productUrl = window.location.href;
    const selectors = await getSelectorsForDomain(shopDomain);

    const priceElement = findElement(selectors.price);
    const currencyElement = findElement(selectors.currency);
    const nameElement = findElement(selectors.name);

    let price = null;
    if (priceElement) {
      price = parsePrice(priceElement.textContent || priceElement.innerText);
    }

    let currency = 'EUR';
    if (currencyElement) {
      currency = parseCurrency(currencyElement.textContent || currencyElement.innerText);
    } else if (priceElement) {
      currency = parseCurrency(priceElement.textContent || priceElement.innerText);
    }

    let productName = null;
    if (nameElement) {
      productName = (nameElement.textContent || nameElement.innerText).trim();
    }

    return {
      data: {
        shop_domain: shopDomain,
        product_url: productUrl,
        price,
        currency,
        product_name: productName,
        name: productName,
        url: productUrl,
        domain: shopDomain,
      },
      elements: { priceElement, nameElement, currencyElement },
    };
  }

  /**
   * Отправляет данные на сервер
   */
  async function sendPriceToServer(data) {
    try {
      // Получаем токен из storage
      const storageData = await chrome.storage.sync.get(['apiToken', 'apiUrl']);
      const token = storageData.apiToken;
      const apiUrl = storageData.apiUrl || 'http://localhost:8000';

      if (!token) {
        console.warn('EMIGRAM: API token not found. Please configure in extension options.');
        return { success: false, error: 'Token not configured' };
      }

      const response = await fetch(`${apiUrl}/api/v1/price/resolve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          shop_domain: data.shop_domain,
          product_url: data.product_url,
          price_store: data.price, // Backend ожидает price_store
          currency: data.currency,
          product_name: data.product_name,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const responseData = await response.json();
      return { success: true, data: responseData };
    } catch (error) {
      console.error('EMIGRAM: Failed to send price:', error);
      return { success: false, error: error.message };
    }
  }

  /**
   * Основная функция парсинга
   */
  async function parseAndSend() {
    const extracted = await extractProductDataWithElements();
    const productData = extracted.data;
    
    if (!productData.price) {
      console.warn('EMIGRAM: Price not found on page');
      return { success: false, error: 'Price not found' };
    }

    console.log('EMIGRAM: Extracted product data:', productData);
    
    const result = await sendPriceToServer(productData);
    
    if (result.success) {
      console.log('EMIGRAM: Price sent successfully:', result.data);

      // Показываем Emigram price рядом с ценой (если удалось найти элемент)
      try {
        applyEmigramBadge(extracted.elements.priceElement, result.data);
      } catch (e) {
        // ignore UI issues
      }

      // Показываем уведомление пользователю
      chrome.runtime.sendMessage({
        type: 'price-sent',
        data: productData,
      });
    } else {
      console.error('EMIGRAM: Failed to send price:', result.error);
    }

    return result;
  }

  function applyEmigramBadge(priceElement, responseData) {
    if (!priceElement || !responseData?.price) return;

    const price = responseData.price;
    const emigram = price.emigram_price;
    const currency = price.currency || '';
    const savings = price.savings_absolute;

    const badgeId = 'emigram-price-badge';
    let badge = document.getElementById(badgeId);
    if (!badge) {
      badge = document.createElement('span');
      badge.id = badgeId;
      badge.style.cssText = `
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 8px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #10B981;
        color: #fff;
        vertical-align: middle;
        white-space: nowrap;
      `;
      // вставляем после цены
      priceElement.insertAdjacentElement('afterend', badge);
    }

    const savingsText = (typeof savings === 'number' && savings > 0)
      ? `• save ${savings.toFixed(2)} ${currency}`
      : '';
    badge.textContent = `EMIGRAM ${Number(emigram).toFixed(2)} ${currency} ${savingsText}`.trim();
  }

  // Слушаем сообщения от popup или background
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'parse-price') {
      parseAndSend().then(result => {
        sendResponse(result);
      });
      return true; // Асинхронный ответ
    }

    if (request.action === 'get-product-data') {
      extractProductDataWithElements().then(({ data }) => {
        sendResponse({ success: true, data });
      }).catch(() => {
        sendResponse({ success: false, data: null });
      });
      return true; // async
    }
  });

  // Автоматический парсинг при загрузке страницы (опционально)
  // Можно включить в настройках
  chrome.storage.sync.get(['autoParse'], (result) => {
    if (result.autoParse) {
      // Небольшая задержка для полной загрузки страницы
      setTimeout(() => {
        parseAndSend();
      }, 2000);
    }
  });

  // Добавляем визуальный индикатор на страницу
  function createIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'emigram-indicator';
    indicator.style.cssText = `
      position: fixed;
      top: 10px;
      right: 10px;
      background: #3B82F6;
      color: white;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 12px;
      z-index: 999999;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    `;
    indicator.textContent = 'EMIGRAM';
    indicator.title = 'Click to parse price';
    
    indicator.addEventListener('click', () => {
      parseAndSend().then(result => {
        if (result.success) {
          indicator.textContent = '✓ Sent';
          indicator.style.background = '#10B981';
          setTimeout(() => {
            indicator.textContent = 'EMIGRAM';
            indicator.style.background = '#3B82F6';
          }, 2000);
        } else {
          indicator.textContent = '✗ Error';
          indicator.style.background = '#EF4444';
          setTimeout(() => {
            indicator.textContent = 'EMIGRAM';
            indicator.style.background = '#3B82F6';
          }, 2000);
        }
      });
    });

    document.body.appendChild(indicator);
  }

  // Создаем индикатор после загрузки DOM (только на страницах товаров)
  function shouldShowIndicator() {
    // Проверяем, есть ли признаки страницы товара
    const hasPrice = findElement(DEFAULT_SELECTORS.price) !== null;
    const hasProductName = findElement(DEFAULT_SELECTORS.name) !== null;
    return hasPrice || hasProductName;
  }

  if (shouldShowIndicator()) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', createIndicator);
    } else {
      createIndicator();
    }
  }
})();

