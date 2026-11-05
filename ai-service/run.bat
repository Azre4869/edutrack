@echo off
cd /D %~dp0
echo ========================================================
echo   Menjalankan EduTrack Local AI Service di Port 8001...
echo ========================================================
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
