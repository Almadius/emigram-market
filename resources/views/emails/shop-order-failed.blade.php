<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка создания заказа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc2626;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        .order-info {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ошибка создания заказа</h1>
    </div>
    
    <div class="content">
        <p>Здравствуйте!</p>
        
        <p>К сожалению, при попытке создать заказ #{{ $orderId }} в магазине <strong>{{ $shopDomain }}</strong> произошла ошибка.</p>
        
        <div class="order-info">
            <h3>Информация о заказе:</h3>
            <p><strong>Номер заказа:</strong> #{{ $orderId }}</p>
            <p><strong>Магазин:</strong> {{ $shopDomain }}</p>
            <p><strong>Сумма:</strong> {{ number_format($total, 2, ',', ' ') }} {{ $currency }}</p>
        </div>
        
        <div class="error-box">
            <h3>Описание ошибки:</h3>
            <p>{{ $error }}</p>
        </div>
        
        <p>Наша команда уже уведомлена об этой проблеме и работает над её решением. Мы свяжемся с вами в ближайшее время.</p>
        
        <p>Если у вас есть вопросы, пожалуйста, свяжитесь с нашей службой поддержки.</p>
        
        <a href="{{ config('app.url') }}/orders" class="button">Посмотреть мои заказы</a>
    </div>
    
    <div class="footer">
        <p>Это автоматическое уведомление от EMIGRAM MARKET.</p>
        <p>Пожалуйста, не отвечайте на это письмо.</p>
    </div>
</body>
</html>









