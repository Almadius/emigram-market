//
//  MainViewController.swift
//  EmigramMarket
//
//  Main view controller with WebView for price parsing
//

import UIKit
import WebKit

class MainViewController: UIViewController {
    
    private var webView: WKWebView!
    private var apiUrl: String = "http://localhost:8000"
    private var apiToken: String?
    private var parseButton: UIButton!
    private var statusLabel: UILabel!
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        title = "EMIGRAM MARKET"
        view.backgroundColor = .systemBackground
        
        setupWebView()
        setupUI()
        loadSettings()
        loadWebView()
    }
    
    private func setupWebView() {
        let configuration = WKWebViewConfiguration()
        configuration.userContentController.add(self, name: "emigramBridge")
        
        webView = WKWebView(frame: .zero, configuration: configuration)
        webView.navigationDelegate = self
        webView.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(webView)
        
        NSLayoutConstraint.activate([
            webView.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor),
            webView.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            webView.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            webView.bottomAnchor.constraint(equalTo: view.safeAreaLayoutGuide.bottomAnchor, constant: -80)
        ])
    }
    
    private func setupUI() {
        // Status label
        statusLabel = UILabel()
        statusLabel.text = "Готов к парсингу"
        statusLabel.textAlignment = .center
        statusLabel.font = .systemFont(ofSize: 14)
        statusLabel.textColor = .systemGray
        statusLabel.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(statusLabel)
        
        // Parse button
        parseButton = UIButton(type: .system)
        parseButton.setTitle("Парсить цену", for: .normal)
        parseButton.backgroundColor = .systemBlue
        parseButton.setTitleColor(.white, for: .normal)
        parseButton.layer.cornerRadius = 8
        parseButton.titleLabel?.font = .boldSystemFont(ofSize: 16)
        parseButton.addTarget(self, action: #selector(parsePrice), for: .touchUpInside)
        parseButton.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(parseButton)
        
        // Settings button
        let settingsButton = UIBarButtonItem(
            image: UIImage(systemName: "gearshape"),
            style: .plain,
            target: self,
            action: #selector(openSettings)
        )
        navigationItem.rightBarButtonItem = settingsButton
        
        NSLayoutConstraint.activate([
            statusLabel.bottomAnchor.constraint(equalTo: parseButton.topAnchor, constant: -8),
            statusLabel.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            statusLabel.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            
            parseButton.bottomAnchor.constraint(equalTo: view.safeAreaLayoutGuide.bottomAnchor, constant: -16),
            parseButton.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            parseButton.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            parseButton.heightAnchor.constraint(equalToConstant: 50)
        ])
    }
    
    private func loadSettings() {
        if let url = UserDefaults.standard.string(forKey: "apiUrl") {
            apiUrl = url
        }
        apiToken = UserDefaults.standard.string(forKey: "apiToken")
    }
    
    private func loadWebView() {
        // Загружаем главную страницу EMIGRAM MARKET или можно открыть любой магазин
        if let url = URL(string: "\(apiUrl)") {
            let request = URLRequest(url: url)
            webView.load(request)
        }
    }
    
    @objc private func parsePrice() {
        guard let token = apiToken else {
            showAlert(title: "Ошибка", message: "API токен не настроен. Перейдите в настройки.")
            return
        }
        
        statusLabel.text = "Парсинг..."
        parseButton.isEnabled = false
        
        // Выполняем JavaScript для извлечения данных о товаре
        let script = """
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
                const cleaned = text.replace(/[^\\d.,]/g, '');
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
            
            return {
                shop_domain: window.location.hostname,
                product_url: window.location.href,
                price: priceEl ? parsePrice(priceEl.textContent) : null,
                currency: priceEl ? parseCurrency(priceEl.textContent) : 'EUR',
                product_name: nameEl ? nameEl.textContent.trim() : null
            };
        })();
        """
        
        webView.evaluateJavaScript(script) { [weak self] result, error in
            guard let self = self else { return }
            
            if let error = error {
                DispatchQueue.main.async {
                    self.statusLabel.text = "Ошибка парсинга"
                    self.parseButton.isEnabled = true
                    self.showAlert(title: "Ошибка", message: error.localizedDescription)
                }
                return
            }
            
            guard let data = result as? [String: Any],
                  let price = data["price"] as? Double,
                  let shopDomain = data["shop_domain"] as? String,
                  let productUrl = data["product_url"] as? String else {
                DispatchQueue.main.async {
                    self.statusLabel.text = "Цена не найдена"
                    self.parseButton.isEnabled = true
                    self.showAlert(title: "Ошибка", message: "Не удалось найти цену на странице")
                }
                return
            }
            
            let currency = data["currency"] as? String ?? "EUR"
            let productName = data["product_name"] as? String
            
            // Отправляем на сервер
            self.sendPriceToServer(
                shopDomain: shopDomain,
                productUrl: productUrl,
                price: price,
                currency: currency,
                productName: productName,
                token: token
            )
        }
    }
    
    private func sendPriceToServer(shopDomain: String, productUrl: String, price: Double, currency: String, productName: String?, token: String) {
        guard let url = URL(string: "\(apiUrl)/api/v1/price/resolve") else {
            DispatchQueue.main.async {
                self.statusLabel.text = "Ошибка URL"
                self.parseButton.isEnabled = true
            }
            return
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        
        let body: [String: Any] = [
            "shop_domain": shopDomain,
            "product_url": productUrl,
            "price_store": price,
            "currency": currency,
            "source": "webview"
        ]
        
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        URLSession.shared.dataTask(with: request) { [weak self] data, response, error in
            guard let self = self else { return }
            
            DispatchQueue.main.async {
                self.parseButton.isEnabled = true
                
                if let error = error {
                    self.statusLabel.text = "Ошибка отправки"
                    self.showAlert(title: "Ошибка", message: error.localizedDescription)
                    return
                }
                
                if let httpResponse = response as? HTTPURLResponse {
                    if httpResponse.statusCode == 200 {
                        self.statusLabel.text = "Цена отправлена!"
                        self.statusLabel.textColor = .systemGreen
                        DispatchQueue.main.asyncAfter(deadline: .now() + 2) {
                            self.statusLabel.text = "Готов к парсингу"
                            self.statusLabel.textColor = .systemGray
                        }
                    } else {
                        self.statusLabel.text = "Ошибка \(httpResponse.statusCode)"
                        self.showAlert(title: "Ошибка", message: "Не удалось отправить цену")
                    }
                }
            }
        }.resume()
    }
    
    @objc private func openSettings() {
        let settingsVC = SettingsViewController()
        let navController = UINavigationController(rootViewController: settingsVC)
        present(navController, animated: true)
    }
    
    private func showAlert(title: String, message: String) {
        let alert = UIAlertController(title: title, message: message, preferredStyle: .alert)
        alert.addAction(UIAlertAction(title: "OK", style: .default))
        present(alert, animated: true)
    }
}

// MARK: - WKNavigationDelegate
extension MainViewController: WKNavigationDelegate {
    func webView(_ webView: WKWebView, didFinish navigation: WKNavigation!) {
        statusLabel.text = "Страница загружена"
    }
    
    func webView(_ webView: WKWebView, didFail navigation: WKNavigation!, withError error: Error) {
        statusLabel.text = "Ошибка загрузки"
        showAlert(title: "Ошибка", message: error.localizedDescription)
    }
}

// MARK: - WKScriptMessageHandler
extension MainViewController: WKScriptMessageHandler {
    func userContentController(_ userContentController: WKUserContentController, didReceive message: WKScriptMessage) {
        // Обработка сообщений от JavaScript
    }
}

