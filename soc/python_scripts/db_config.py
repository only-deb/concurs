import mysql.connector

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="test1",
        password="IgNc8Otoa8WBrdrX",
        database="test1"
    )