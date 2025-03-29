import sys
sys.stdout.reconfigure(encoding='utf-8')
import random
import markovify
from db_config import get_db_connection
from analyze_sentiment import analyze_sentiment

# Функция для обрезки текста
def truncate_text(text, max_length):
    return text[:max_length].strip()

# Получение комментариев из базы данных для обучения модели
def get_existing_comments():
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "SELECT content FROM comments"
    cursor.execute(query)
    comments = [row[0] for row in cursor.fetchall()]
    cursor.close()
    conn.close()
    return comments

# Очистка данных
def clean_text(text):
    text = text.strip()
    sentences = text.split(".")
    return [sentence.strip() for sentence in sentences if sentence.strip()]

# Получение данных для обучения
def get_training_data():
    existing_comments = get_existing_comments()
    cleaned_comments = []
    for comment in existing_comments:
        cleaned_comments.extend(clean_text(comment))
    return cleaned_comments + default_comments

# Создание модели Markovify на основе существующих комментариев
def create_markov_model():
    training_data = "\n".join(get_training_data())
    print(f"Используется {len(training_data.splitlines())} строк для обучения модели.")
    return markovify.Text(training_data, state_size=2)

# Модификация текста для уникальности
def modify_text(text):
    synonyms = {
        "отлично": ["прекрасно", "замечательно", "хорошо"],
        "интересно": ["увлекательно", "захватывающе", "важно"],
        "нравится": ["понравилось", "пришлось по душе", "оценено"],
        "странно": ["необычно", "непривычно", "удивительно"],
        "сомневаюсь": ["колеблюсь", "не уверен", "размышляю"],
        "понравилось": ["порадовало", "впечатлило", "запомнилось"]
    }
    random_phrases = [
        "Отличный пост! Спасибо за информацию.",
    "Согласен с вами. Это действительно важно.",
    "Это очень интересно. Хочу узнать больше.",
    "Мне нравится ваш подход. Продолжайте в том же духе!",
    "Здорово! Это вдохновляет.",
    "Не согласен. Есть другие точки зрения.",
    "Это странно. Нужно подумать над этим.",
    "Сомневаюсь. Не уверен, что это работает.",
    "Это не имеет смысла. Объясните подробнее.",
    "Не понравилось. Есть место для улучшений.",
    "Спасибо за ваш труд. Это полезно.",
    "Ваше мнение имеет вес. Учту это.",
    "Продолжайте в том же духе! Вы молодцы.",
    "Это действительно полезная информация.",
    "Думаю, это спорно. Нужно обсудить.",
    "Ваш подход отличается от других. Интересно!",
    "Сложно сказать. Нужно больше данных.",
    "Это важный момент. Благодарю за разъяснение.",
    "Спасибо за ваше мнение. Оно ценно.",
    "Не совсем понял. Можете уточнить?",
    "Это хороший шаг вперёд. Поддерживаю!"
    ]

    words = text.split()
    for i, word in enumerate(words):
        if word in synonyms:
            words[i] = random.choice(synonyms[word])
    if random.random() < 0.5:
        words.append(random.choice(random_phrases))
    return " ".join(words)

# Генерация комментария через Markovify
def generate_comment_with_model(sentiment, text_model):
    max_attempts = 100
    for attempt in range(max_attempts):
        comment = text_model.make_sentence()
        if comment:
            comment = modify_text(comment)
            if sentiment == "positive" and any(word in comment.lower() for word in ["отлично", "интересно", "нравится"]):
                return comment
            elif sentiment == "negative" and any(word in comment.lower() for word in ["странно", "сомневаюсь", "не понравилось"]):
                return comment
    fallback_positive = ["Отличный пост!", "Согласен с вами.", "Это очень интересно."]
    fallback_negative = ["Не согласен.", "Это странно.", "Сомневаюсь."]
    return random.choice(fallback_positive if sentiment == "positive" else fallback_negative)

# Добавление комментария в базу данных
def add_comment_to_db(post_id, user_id, content):
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "INSERT INTO comments (post_id, user_id, content) VALUES (%s, %s, %s)"
    cursor.execute(query, (post_id, user_id, content))
    conn.commit()
    cursor.close()
    conn.close()

# Добавление реакции в базу данных
def add_reaction_to_db(post_id, user_id, reaction_type):
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "INSERT INTO likes (post_id, user_id, type) VALUES (%s, %s, %s)"
    cursor.execute(query, (post_id, user_id, reaction_type))
    conn.commit()
    cursor.close()
    conn.close()

# Генерация комментариев и реакций
def generate_comments_and_reactions():
    # Создаем модель Markovify
    text_model = create_markov_model()

    # Получаем посты и пользователей
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT id, content FROM posts")
    posts = cursor.fetchall()
    cursor.execute("SELECT id FROM users")
    user_ids = [row[0] for row in cursor.fetchall()]
    cursor.close()
    conn.close()

    for post_id, content in posts:
        sentiment = analyze_sentiment(content)

        # Добавляем случайное количество реакций (от 1 до 5)
        for _ in range(random.randint(1, 5)):
            # Случайный выбор типа реакции
            reaction_type = random.choice(["like", "dislike"])
            user_id = random.choice(user_ids)

            # Добавляем реакцию
            add_reaction_to_db(post_id, user_id, reaction_type)
            print(f"Добавлена реакция {reaction_type} к посту {post_id}")

            # Генерируем комментарий в зависимости от типа реакции
            comment = generate_comment_with_model("positive" if reaction_type == "like" else "negative", text_model)

            # Добавляем комментарий
            add_comment_to_db(post_id, user_id, comment)
            print(f"Добавлен комментарий к посту {post_id}: {comment}")

# Фиксированные примеры текстов
default_comments = [
    "Отличный пост! Спасибо за информацию.",
    "Согласен с вами. Это действительно важно.",
    "Это очень интересно. Хочу узнать больше.",
    "Мне нравится ваш подход. Продолжайте в том же духе!",
    "Здорово! Это вдохновляет.",
    "Не согласен. Есть другие точки зрения.",
    "Это странно. Нужно подумать над этим.",
    "Сомневаюсь. Не уверен, что это работает.",
    "Это не имеет смысла. Объясните подробнее.",
    "Не понравилось. Есть место для улучшений.",
    "Спасибо за ваш труд. Это полезно.",
    "Ваше мнение имеет вес. Учту это.",
    "Продолжайте в том же духе! Вы молодцы.",
    "Это действительно полезная информация.",
    "Думаю, это спорно. Нужно обсудить.",
    "Ваш подход отличается от других. Интересно!",
    "Сложно сказать. Нужно больше данных.",
    "Это важный момент. Благодарю за разъяснение.",
    "Спасибо за ваше мнение. Оно ценно.",
    "Не совсем понял. Можете уточнить?",
    "Это хороший шаг вперёд. Поддерживаю!"
]

# Запуск генерации комментариев и реакций
if __name__ == "__main__":
    generate_comments_and_reactions()