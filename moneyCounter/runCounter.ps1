python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
# .\.venv\Scripts\python.exe -m pip install -r requirements-mysql.txt
# .\.venv\Scripts\python.exe mysql_backup.py once

# .\.venv\Scripts\python.exe mysql_backup.py watch --interval 60

.\.venv\Scripts\python.exe app.py