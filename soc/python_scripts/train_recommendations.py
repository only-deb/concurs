import mysql.connector
import pandas as pd
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np
import time

# Подключение к базе данных
def connect_to_db():
    return mysql.connector.connect(
        host="localhost",
        user="test1",
        password="IgNc8Otoa8WBrdrX",
        database="test1"
    )

# Загрузка данных о лайках, дизлайках и комментариях
def load_data(cursor):
    query = """
        SELECT 
            likes.user_id, 
            likes.post_id, 
            CASE 
                WHEN likes.type = 'like' THEN 1 
                WHEN likes.type = 'dislike' THEN -1 
                ELSE 0 
            END AS rating
        FROM likes
        UNION ALL
        SELECT 
            comments.user_id, 
            comments.post_id, 
            0.5 AS rating -- Комментарий считаем менее значимым, чем лайк
        FROM comments
    """
    cursor.execute(query)
    data = cursor.fetchall()
    return pd.DataFrame(data)

# Генерация рекомендаций
def recommend_posts(user_id, user_similarity_df, user_item_matrix, top_n=5):
    similar_users = user_similarity_df[user_id].sort_values(ascending=False).index[1:]
    scores = user_item_matrix.loc[similar_users].sum().sort_values(ascending=False)
    rated_posts = user_item_matrix.loc[user_id]
    scores = scores[rated_posts == 0]
    return scores.head(top_n)

# Основной цикл
if __name__ == "__main__":
    while True:
        try:
            db = connect_to_db()
            cursor = db.cursor(dictionary=True)

            # Загрузка данных
            df = load_data(cursor)

            if df.empty:
                print("Нет данных для анализа.")
                db.close()
                time.sleep(300)  # Ждем 5 минут перед следующей попыткой
                continue

            # Преобразование данных в матрицу "пользователь-пост"
            user_item_matrix = df.pivot_table(index='user_id', columns='post_id', values='rating', fill_value=0)

            # Вычисление косинусного сходства между пользователями
            user_similarity = cosine_similarity(user_item_matrix)
            user_similarity_df = pd.DataFrame(user_similarity, index=user_item_matrix.index, columns=user_item_matrix.index)

            # Генерация рекомендаций для всех пользователей
            all_recommendations = []
            for user_id in user_item_matrix.index:
                recommendations = recommend_posts(user_id, user_similarity_df, user_item_matrix)
                for post_id, score in recommendations.items():
                    all_recommendations.append((user_id, post_id, score))

            # Очистка старых рекомендаций
            cursor.execute("DELETE FROM recommendations")
            db.commit()

            # Сохранение новых рекомендаций в базу данных
            insert_query = "INSERT INTO recommendations (user_id, post_id, score) VALUES (%s, %s, %s)"
            cursor.executemany(insert_query, all_recommendations)
            db.commit()

            print("Рекомендации успешно обновлены!")
        except Exception as e:
            print(f"Ошибка: {e}")
        finally:
            db.close()

        # Пауза перед следующим обновлением
        time.sleep(300)  # 300 секунд = 5 минут