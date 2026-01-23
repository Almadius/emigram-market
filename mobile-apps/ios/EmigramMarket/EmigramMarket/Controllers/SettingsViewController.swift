//
//  SettingsViewController.swift
//  EmigramMarket
//
//  Settings screen for API configuration
//

import UIKit

class SettingsViewController: UIViewController {
    
    private var apiUrlTextField: UITextField!
    private var apiTokenTextField: UITextField!
    private var saveButton: UIButton!
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        title = "Настройки"
        view.backgroundColor = .systemBackground
        
        navigationItem.leftBarButtonItem = UIBarButtonItem(
            barButtonSystemItem: .cancel,
            target: self,
            action: #selector(dismissSettings)
        )
        
        setupUI()
        loadSettings()
    }
    
    private func setupUI() {
        // API URL
        let urlLabel = UILabel()
        urlLabel.text = "URL API"
        urlLabel.font = .systemFont(ofSize: 16, weight: .medium)
        urlLabel.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(urlLabel)
        
        apiUrlTextField = UITextField()
        apiUrlTextField.placeholder = "http://localhost:8000"
        apiUrlTextField.borderStyle = .roundedRect
        apiUrlTextField.keyboardType = .URL
        apiUrlTextField.autocapitalizationType = .none
        apiUrlTextField.autocorrectionType = .no
        apiUrlTextField.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(apiUrlTextField)
        
        // API Token
        let tokenLabel = UILabel()
        tokenLabel.text = "API Token"
        tokenLabel.font = .systemFont(ofSize: 16, weight: .medium)
        tokenLabel.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(tokenLabel)
        
        apiTokenTextField = UITextField()
        apiTokenTextField.placeholder = "Ваш токен авторизации"
        apiTokenTextField.borderStyle = .roundedRect
        apiTokenTextField.isSecureTextEntry = true
        apiTokenTextField.autocapitalizationType = .none
        apiTokenTextField.autocorrectionType = .no
        apiTokenTextField.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(apiTokenTextField)
        
        // Save button
        saveButton = UIButton(type: .system)
        saveButton.setTitle("Сохранить", for: .normal)
        saveButton.backgroundColor = .systemBlue
        saveButton.setTitleColor(.white, for: .normal)
        saveButton.layer.cornerRadius = 8
        saveButton.titleLabel?.font = .boldSystemFont(ofSize: 16)
        saveButton.addTarget(self, action: #selector(saveSettings), for: .touchUpInside)
        saveButton.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(saveButton)
        
        NSLayoutConstraint.activate([
            urlLabel.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor, constant: 32),
            urlLabel.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            urlLabel.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            
            apiUrlTextField.topAnchor.constraint(equalTo: urlLabel.bottomAnchor, constant: 8),
            apiUrlTextField.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            apiUrlTextField.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            apiUrlTextField.heightAnchor.constraint(equalToConstant: 44),
            
            tokenLabel.topAnchor.constraint(equalTo: apiUrlTextField.bottomAnchor, constant: 24),
            tokenLabel.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            tokenLabel.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            
            apiTokenTextField.topAnchor.constraint(equalTo: tokenLabel.bottomAnchor, constant: 8),
            apiTokenTextField.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            apiTokenTextField.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            apiTokenTextField.heightAnchor.constraint(equalToConstant: 44),
            
            saveButton.topAnchor.constraint(equalTo: apiTokenTextField.bottomAnchor, constant: 32),
            saveButton.leadingAnchor.constraint(equalTo: view.leadingAnchor, constant: 16),
            saveButton.trailingAnchor.constraint(equalTo: view.trailingAnchor, constant: -16),
            saveButton.heightAnchor.constraint(equalToConstant: 50)
        ])
    }
    
    private func loadSettings() {
        apiUrlTextField.text = UserDefaults.standard.string(forKey: "apiUrl") ?? "http://localhost:8000"
        apiTokenTextField.text = UserDefaults.standard.string(forKey: "apiToken")
    }
    
    @objc private func saveSettings() {
        guard let url = apiUrlTextField.text, !url.isEmpty,
              let token = apiTokenTextField.text, !token.isEmpty else {
            showAlert(title: "Ошибка", message: "Заполните все поля")
            return
        }
        
        // Валидация URL
        guard URL(string: url) != nil else {
            showAlert(title: "Ошибка", message: "Некорректный URL")
            return
        }
        
        UserDefaults.standard.set(url, forKey: "apiUrl")
        UserDefaults.standard.set(token, forKey: "apiToken")
        
        showAlert(title: "Успешно", message: "Настройки сохранены") {
            self.dismissSettings()
        }
    }
    
    @objc private func dismissSettings() {
        dismiss(animated: true)
    }
    
    private func showAlert(title: String, message: String, completion: (() -> Void)? = nil) {
        let alert = UIAlertController(title: title, message: message, preferredStyle: .alert)
        alert.addAction(UIAlertAction(title: "OK", style: .default) { _ in
            completion?()
        })
        present(alert, animated: true)
    }
}

