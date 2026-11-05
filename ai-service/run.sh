#!/usr/bin/env bash
cd "$(dirname "$0")"
echo "Menjalankan EduTrack Local AI Service di 127.0.0.1:8001..."
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
