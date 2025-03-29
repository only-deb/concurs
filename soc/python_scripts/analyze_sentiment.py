import sys
sys.stdout.reconfigure(encoding='utf-8')
from textblob import TextBlob

def analyze_sentiment(text):
    blob = TextBlob(text)
    polarity = blob.sentiment.polarity
    return "positive" if polarity > 0 else "negative"

# Генерация комментария
def generate_comment(sentiment):
    if sentiment == "positive":
        return "Отличный пост! Продолжайте в том же духе."
    else:
        return "Не согласен. Нужно работать над этим."