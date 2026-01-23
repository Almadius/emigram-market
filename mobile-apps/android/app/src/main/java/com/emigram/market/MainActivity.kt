package com.emigram.market

import android.os.Bundle
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import org.json.JSONObject

class MainActivity : AppCompatActivity() {
    
    private lateinit var webView: WebView
    private lateinit var parseButton: Button
    private lateinit var statusLabel: TextView
    
    private var apiUrl: String = "http://localhost:8000"
    private var apiToken: String? = null
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        
        loadSettings()
        setupWebView()
        setupUI()
        loadWebView()
    }
    
    private fun loadSettings() {
        val prefs = getSharedPreferences("emigram_prefs", MODE_PRIVATE)
        apiUrl = prefs.getString("api_url", "http://localhost:8000") ?: "http://localhost:8000"
        apiToken = prefs.getString("api_token", null)
    }
    
    private fun setupWebView() {
        webView = findViewById(R.id.webView)
        
        val webSettings = webView.settings
        webSettings.javaScriptEnabled = true
        webSettings.domStorageEnabled = true
        webSettings.loadWithOverviewMode = true
        webSettings.useWideViewPort = true
        
        // Добавляем JavaScript интерфейс
        webView.addJavascriptInterface(WebAppInterface(), "Android")
        
        webView.webViewClient = object : WebViewClient() {
            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                statusLabel.text = "Страница загружена"
            }
        }
    }
    
    private fun setupUI() {
        parseButton = findViewById(R.id.parseButton)
        statusLabel = findViewById(R.id.statusLabel)
        
        parseButton.setOnClickListener {
            parsePrice()
        }
        
        // Кнопка настроек в ActionBar
        supportActionBar?.setDisplayHomeAsUpEnabled(false)
        supportActionBar?.title = "EMIGRAM MARKET"
    }
    
    private fun loadWebView() {
        webView.loadUrl(apiUrl)
    }
    
    private fun parsePrice() {
        if (apiToken == null) {
            Toast.makeText(this, "API токен не настроен", Toast.LENGTH_SHORT).show()
            return
        }
        
        statusLabel.text = "Парсинг..."
        parseButton.isEnabled = false
        
        val script = """
            (function() {
                function findElement(selectors) {
                    for (const selector of selectors) {
                        try {
                            const element = document.querySelector(selector);
                            if (element) return element;
                        } catch (e) {}
                    }
                    return null;
                }
                
                function parsePrice(text) {
                    if (!text) return null;
                    const cleaned = text.replace(/[^\d.,]/g, '');
                    const normalized = cleaned.replace(',', '.');
                    const price = parseFloat(normalized);
                    return isNaN(price) || price <= 0 ? null : price;
                }
                
                function parseCurrency(text) {
                    if (!text) return null;
                    const currencyMap = { '€': 'EUR', '$': 'USD', '£': 'GBP', '₽': 'RUB' };
                    const upperText = text.toUpperCase();
                    for (const [symbol, code] of Object.entries(currencyMap)) {
                        if (upperText.includes(symbol) || upperText.includes(code)) return code;
                    }
                    return 'EUR';
                }
                
                const selectors = {
                    price: ['[data-price]', '.price', '.product-price', '.price-current', '[class*="price"]'],
                    name: ['h1', '.product-title', '[data-product-name]']
                };
                
                const priceEl = findElement(selectors.price);
                const nameEl = findElement(selectors.name);
                
                const data = {
                    shop_domain: window.location.hostname,
                    product_url: window.location.href,
                    price: priceEl ? parsePrice(priceEl.textContent) : null,
                    currency: priceEl ? parseCurrency(priceEl.textContent) : 'EUR',
                    product_name: nameEl ? nameEl.textContent.trim() : null
                };
                
                Android.onPriceParsed(JSON.stringify(data));
            })();
        """.trimIndent()
        
        webView.evaluateJavascript(script, null)
    }
    
    private fun sendPriceToServer(
        shopDomain: String,
        productUrl: String,
        price: Double,
        currency: String,
        productName: String?
    ) {
        val url = "$apiUrl/api/v1/price/resolve"
        
        val jsonBody = JSONObject().apply {
            put("shop_domain", shopDomain)
            put("product_url", productUrl)
            put("price_store", price)
            put("currency", currency)
            put("source", "webview")
        }
        
        val request = okhttp3.Request.Builder()
            .url(url)
            .post(okhttp3.RequestBody.create(
                okhttp3.MediaType.parse("application/json"),
                jsonBody.toString()
            ))
            .addHeader("Content-Type", "application/json")
            .addHeader("Authorization", "Bearer $apiToken")
            .addHeader("Accept", "application/json")
            .build()
        
        okhttp3.OkHttpClient().newCall(request).enqueue(object : okhttp3.Callback {
            override fun onFailure(call: okhttp3.Call, e: java.io.IOException) {
                runOnUiThread {
                    statusLabel.text = "Ошибка отправки"
                    parseButton.isEnabled = true
                    Toast.makeText(this@MainActivity, e.message, Toast.LENGTH_SHORT).show()
                }
            }
            
            override fun onResponse(call: okhttp3.Call, response: okhttp3.Response) {
                runOnUiThread {
                    parseButton.isEnabled = true
                    if (response.isSuccessful) {
                        statusLabel.text = "Цена отправлена!"
                        Toast.makeText(this@MainActivity, "Цена успешно отправлена", Toast.LENGTH_SHORT).show()
                    } else {
                        statusLabel.text = "Ошибка ${response.code()}"
                        Toast.makeText(this@MainActivity, "Не удалось отправить цену", Toast.LENGTH_SHORT).show()
                    }
                }
            }
        })
    }
    
    inner class WebAppInterface {
        @JavascriptInterface
        fun onPriceParsed(jsonData: String) {
            try {
                val data = JSONObject(jsonData)
                val price = data.optDouble("price")
                
                if (price <= 0 || price.isNaN()) {
                    runOnUiThread {
                        statusLabel.text = "Цена не найдена"
                        parseButton.isEnabled = true
                        Toast.makeText(this@MainActivity, "Не удалось найти цену на странице", Toast.LENGTH_SHORT).show()
                    }
                    return
                }
                
                val shopDomain = data.getString("shop_domain")
                val productUrl = data.getString("product_url")
                val currency = data.optString("currency", "EUR")
                val productName = data.optString("product_name")
                
                sendPriceToServer(shopDomain, productUrl, price, currency, productName)
            } catch (e: Exception) {
                runOnUiThread {
                    statusLabel.text = "Ошибка парсинга"
                    parseButton.isEnabled = true
                    Toast.makeText(this@MainActivity, e.message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }
}

