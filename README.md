## Schedule API based on Lumen PHP Framework

### Устанавливаем приложение и зависимости
```bash
git clone https://github.com/blind3y3/schedule.git
cd schedule && composer install
cp .env.example .env
```

### Генерируем APP_KEY и вставляем полученную строку в .env, в секцию APP_KEY
```php
php -r "echo bin2hex(random_bytes(32));"
```

### Запускаем приложение 
```bash
php -S localhost:8080 -t public
```

### Переходим по адресу http://localhost:8080/schedule?startDate=1.1.2018&endDate=1.4.2018&userId=1
### API работает с форматами Y-m-y и d.m.Y
