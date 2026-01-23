package com.emigram.market

import android.content.SharedPreferences
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class SettingsActivity : AppCompatActivity() {
    
    private lateinit var apiUrlEditText: EditText
    private lateinit var apiTokenEditText: EditText
    private lateinit var saveButton: Button
    
    private lateinit var prefs: SharedPreferences
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_settings)
        
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        supportActionBar?.title = "Настройки"
        
        prefs = getSharedPreferences("emigram_prefs", MODE_PRIVATE)
        
        apiUrlEditText = findViewById(R.id.apiUrlEditText)
        apiTokenEditText = findViewById(R.id.apiTokenEditText)
        saveButton = findViewById(R.id.saveButton)
        
        loadSettings()
        
        saveButton.setOnClickListener {
            saveSettings()
        }
    }
    
    private fun loadSettings() {
        apiUrlEditText.setText(prefs.getString("api_url", "http://localhost:8000"))
        apiTokenEditText.setText(prefs.getString("api_token", ""))
    }
    
    private fun saveSettings() {
        val url = apiUrlEditText.text.toString().trim()
        val token = apiTokenEditText.text.toString().trim()
        
        if (url.isEmpty() || token.isEmpty()) {
            Toast.makeText(this, "Заполните все поля", Toast.LENGTH_SHORT).show()
            return
        }
        
        // Простая валидация URL
        if (!url.startsWith("http://") && !url.startsWith("https://")) {
            Toast.makeText(this, "Некорректный URL", Toast.LENGTH_SHORT).show()
            return
        }
        
        prefs.edit()
            .putString("api_url", url)
            .putString("api_token", token)
            .apply()
        
        Toast.makeText(this, "Настройки сохранены", Toast.LENGTH_SHORT).show()
        finish()
    }
    
    override fun onSupportNavigateUp(): Boolean {
        finish()
        return true
    }
}

