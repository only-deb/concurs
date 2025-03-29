import random
from db_config import get_db_connection
import uuid
import sys

# Настройка кодировки для вывода
sys.stdout.reconfigure(encoding='utf-8')

# Генерация имени
def generate_name():
    prefixes = ["Ива", "Але", "Мари", "Ан", "Дми", "Соф", "Арт", "Еле", "Ник", "Ол"]
    suffixes = ["н", "ксей", "я", "на", "трий", "ья", "ем", "на", "ита", "ьга"]
    return random.choice(prefixes) + random.choice(suffixes)

# Генерация города
def generate_city():
    cities = ["Москва", "Санкт-Петербург", "Новосибирск", "Екатеринбург", "Казань"]
    return random.choice(cities)

# Генерация логина
def generate_username():
    words = ["user", "admin", "guest", "test", "new", "super", "mega", "pro"]
    numbers = random.randint(100, 999)
    return f"{random.choice(words)}{numbers}"

# Генерация пароля
def generate_password():
    passwords = ["securepassword123", "mypassword", "qwerty123", "password1234", "letmein"]
    return random.choice(passwords)

# Проверка уникальности email
def is_email_unique(email):
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "SELECT COUNT(*) FROM users WHERE email = %s"
    cursor.execute(query, (email,))
    count = cursor.fetchone()[0]
    cursor.close()
    conn.close()
    return count == 0

# Генерация уникального email
def generate_unique_email(username):
    while True:
        email = f"{username}_{uuid.uuid4().hex[:6]}@example.com"
        if is_email_unique(email):
            return email

# Проверка уникальности username
def is_username_unique(username):
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "SELECT COUNT(*) FROM users WHERE username = %s"
    cursor.execute(query, (username,))
    count = cursor.fetchone()[0]
    cursor.close()
    conn.close()
    return count == 0

# Добавление пользователя в базу данных
def add_user_to_db(username, email, password, first_name, last_name, city):
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "INSERT INTO users (username, email, password, first_name, last_name, city) VALUES (%s, %s, %s, %s, %s, %s)"
    cursor.execute(query, (username, email, password, first_name, last_name, city))
    conn.commit()
    cursor.close()
    conn.close()

# Генерация и добавление пользователей
for _ in range(50):  # Генерация 50 пользователей
    while True:
        username = generate_username()
        if is_username_unique(username):
            break
    email = generate_unique_email(username)
    password = generate_password()
    first_name = generate_name()
    last_name = generate_name()
    city = generate_city()
    add_user_to_db(username, email, password, first_name, last_name, city)
    print(f"Добавлен пользователь: {first_name} {last_name} ({username}, {email})")