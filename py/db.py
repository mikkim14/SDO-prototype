import sqlite3

DB_FILE = "local_hub.db"

def init_db():
    """Creates the database and table if they don't exist."""
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS meters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            meter_id TEXT UNIQUE,
            campus TEXT,
            api_address TEXT,
            type TEXT DEFAULT 'electric'
        )
    ''')
    conn.commit()
    conn.close()

def get_all_meters():
    """Returns a list of all meters from the database."""
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("SELECT meter_id, campus, api_address, type FROM meters")
    rows = cursor.fetchall()
    conn.close()
    return rows

def add_meter_to_db(meter_id, campus, api_address, m_type='electric'):
    """Inserts a new meter into the local database."""
    try:
        conn = sqlite3.connect(DB_FILE)
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO meters (meter_id, campus, api_address, type) VALUES (?, ?, ?, ?)",
            (meter_id, campus, api_address, m_type)
        )
        conn.commit()
        conn.close()
        return True
    except sqlite3.IntegrityError:
        return False # Meter ID already exists