from flask import Flask, request, jsonify
import mysql.connector
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import nltk
from nltk.corpus import stopwords
from nltk.sentiment import SentimentIntensityAnalyzer
import string
import json
from decimal import Decimal

app = Flask(__name__)

# Загрузка стоп-слов и VADER для анализа тональности
nltk.download('stopwords')
nltk.download('vader_lexicon')
stop_words = set(stopwords.words('russian'))
sia = SentimentIntensityAnalyzer()

# Конфигурации для всех баз данных
db_configs = {
    "test1.onlydeb": {
        "host": "localhost",
        "user": "onlydeb",
        "password": "21ufeguz",
        "database": "test1_onlyde"
    },
    "test2.onlydeb": {
        "host": "localhost",
        "user": "onlydeb",
        "password": "21ufeguz",
        "database": "test2_onlyde"
    },
    "test1.onlydeb": {
        "host": "localhost",
        "user": "onlydeb",
        "password": "21ufeguz",
        "database": "test3_onlyde"
    }
}

# Подключение к базам данных
def connect_to_db(host, user, password, database):
    return mysql.connector.connect(
        host=host,
        user=user,
        password=password,
        database=database
    )

# Первый контур: Сканирование баз данных
def scan_social_networks():
    all_companies = set()
    mentions = {"positive": 0, "neutral": 0, "negative": 0}
    total_posts_scanned = 0

    for sn, config in db_configs.items():
        db = connect_to_db(**config)
        cursor = db.cursor(dictionary=True)

        # Получаем все компании из базы данных
        cursor.execute("SELECT DISTINCT company FROM users")
        companies = [row['company'] for row in cursor.fetchall() if row['company']]
        all_companies.update(companies)

        # Сканируем посты на наличие упоминаний компаний
        cursor.execute("""
            SELECT posts.content, users.company
            FROM posts
            LEFT JOIN users ON posts.user_id = users.id
        """)
        posts = cursor.fetchall()
        total_posts_scanned += len(posts)

        for post in posts:
            content = post['content']
            company = post['company']
            if any(c.lower() in content.lower() for c in all_companies):
                sentiment = sia.polarity_scores(content)
                if sentiment['compound'] > 0.05:
                    mentions['positive'] += 1
                elif sentiment['compound'] < -0.05:
                    mentions['negative'] += 1
                else:
                    mentions['neutral'] += 1

        db.close()

    return {
        "total_posts_scanned": total_posts_scanned,
        "mentions": mentions
    }

# Второй контур: Анализ бизнеса и поиск конкурентов
def analyze_business(social_network, user_id):
    db_config = db_configs.get(social_network)
    if not db_config:
        return {"error": "Неподдерживаемая социальная сеть"}, 400

    db = connect_to_db(**db_config)
    cursor = db.cursor(dictionary=True)

    # Получаем профиль пользователя
    cursor.execute("""
        SELECT 
            GROUP_CONCAT(posts.content SEPARATOR ' ') AS content,
            activity, 
            company
        FROM users
        LEFT JOIN posts ON users.id = posts.user_id
        WHERE users.id = %s
        GROUP BY users.id
    """, (user_id,))
    profile = cursor.fetchone()

    main_profile_text = " ".join([
        profile['content'] or "",
        profile['activity'] or "",
        profile['company'] or ""
    ])

    # Получаем всех пользователей из обеих соцсетей
    all_profiles = []
    for sn, config in db_configs.items():
        other_db = connect_to_db(**config)
        other_cursor = other_db.cursor(dictionary=True)
        query = """
            SELECT 
                users.id, 
                users.first_name, 
                users.last_name, 
                users.city, 
                users.activity, 
                users.company,
                COUNT(posts.id) AS post_count,
                SUM(CASE WHEN likes.type = 'like' THEN 1 ELSE 0 END) AS likes_count,
                SUM(CASE WHEN likes.type = 'dislike' THEN 1 ELSE 0 END) AS dislikes_count,
                GROUP_CONCAT(comments.content SEPARATOR '|') AS comments_text
            FROM users
            LEFT JOIN posts ON users.id = posts.user_id
            LEFT JOIN likes ON posts.id = likes.post_id
            LEFT JOIN comments ON posts.id = comments.post_id
            GROUP BY users.id
        """
        other_cursor.execute(query)
        profiles = other_cursor.fetchall()
        for profile in profiles:
            profile['social_network'] = sn
        all_profiles.extend(profiles)
        other_db.close()

    vectorizer = TfidfVectorizer()
    all_profiles_texts = [
        " ".join([
            profile['activity'] or "",
            profile['company'] or ""
        ]) for profile in all_profiles
    ]
    all_profiles_vectors = vectorizer.fit_transform(all_profiles_texts)

    main_profile_vector = vectorizer.transform([main_profile_text])

    similarities = cosine_similarity(main_profile_vector, all_profiles_vectors).flatten()
    sorted_indices = similarities.argsort()[::-1]
    competitors = [all_profiles[i] for i in sorted_indices[:5]]

    # Группировка по компаниям
    grouped_companies = {}
    for competitor in competitors:
        company = competitor['company']
        if company not in grouped_companies:
            grouped_companies[company] = {
                "company": company,
                "profiles": [],
                "total_likes": 0,
                "total_dislikes": 0,
                "total_posts": 0,
                "sentiment_scores": {"positive": 0, "neutral": 0, "negative": 0}
            }
        grouped_companies[company]['profiles'].append({
            "name": f"{competitor['first_name']} {competitor['last_name']}",
            "city": competitor['city'],
            "profile_url": f"http://{competitor['social_network']}.online/user_profile.php?user_id={competitor['id']}"
        })
        grouped_companies[company]['total_likes'] += competitor['likes_count'] or 0
        grouped_companies[company]['total_dislikes'] += competitor['dislikes_count'] or 0
        grouped_companies[company]['total_posts'] += competitor['post_count'] or 0

        # Анализ тональности комментариев
        comments_text = competitor['comments_text'] or ""
        comment_list = comments_text.split('|')
        sentiment_scores = {"positive": 0, "neutral": 0, "negative": 0}
        for comment in comment_list:
            if comment.strip():
                sentiment = sia.polarity_scores(comment)
                if sentiment['compound'] > 0.05:
                    sentiment_scores['positive'] += 1
                elif sentiment['compound'] < -0.05:
                    sentiment_scores['negative'] += 1
                else:
                    sentiment_scores['neutral'] += 1

        grouped_companies[company]['sentiment_scores']['positive'] += sentiment_scores['positive']
        grouped_companies[company]['sentiment_scores']['neutral'] += sentiment_scores['neutral']
        grouped_companies[company]['sentiment_scores']['negative'] += sentiment_scores['negative']

    result = []
    for company, data in grouped_companies.items():
        result.append({
            "company": company,
            "profiles": data['profiles'],
            "total_likes": float(data['total_likes']),
            "total_dislikes": float(data['total_dislikes']),
            "total_posts": float(data['total_posts']),
            "sentiment_scores": data['sentiment_scores']
        })

    return result

@app.route('/analyze', methods=['POST'])
def analyze():
    data = request.json
    social_network = data.get('social_network')
    user_id = data.get('user_id')

    if not social_network or not user_id:
        return jsonify({"error": "Необходимо передать social_network и user_id"}), 400

    try:
        user_id = int(user_id)
    except ValueError:
        return jsonify({"error": "Некорректный user_id"}), 400

    # Первый контур: Сканирование баз данных
    scan_results = scan_social_networks()

    # Второй контур: Анализ бизнеса
    business_results = analyze_business(social_network, user_id)

    return jsonify({
        "scan_results": scan_results,
        "business_results": business_results
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)