import pymysql

conn = pymysql.connect(
    host='127.0.0.1',
    user='pictro',
    password='pictro!@#$',
    database='Pictro',
    autocommit=True
)
with conn.cursor() as c:
    c.execute("UPDATE api_client_user SET plan_id = 'ENTERPRISE' WHERE client_id = 'client_foxunni_master'")
    print(f"Updated plan rows: {c.rowcount}")

conn.close()
