# Gebruik een Python base image
FROM python:3.11-slim

# Stel de werkdirectory in
WORKDIR /app

# Kopieer de inhoud van je project naar de container
COPY . /app

# Installeer de vereiste Python-pakketten
RUN pip install --no-cache-dir -r requirements.txt

# Start je script; vervang 'jouw_script.py' door de naam van je script
CMD ["python", "Substack_note-creator_telegram.py"]
