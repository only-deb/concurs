import sys
sys.stdout.reconfigure(encoding='utf-8')
import requests

API_TOKEN = "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"  # Замените на ваш токен
API_URL = "https://api-inference.huggingface.co/models/gpt2"

headers = {
    "Authorization": f"Bearer {API_TOKEN}",
    "Content-Type": "application/json"
}

def generate_text(prompt, max_length=50):
    payload = {
        "inputs": prompt,
        "max_length": max_length,
        "num_return_sequences": 1
    }
    response = requests.post(API_URL, headers=headers, json=payload)
    if response.status_code == 200:
        return response.json()[0]['generated_text']
    else:
        print("Ошибка при генерации текста:", response.text)
        return None