@echo off
cd /d "%~dp0"
py vehicle_tracker.py
if errorlevel 1 (
  echo Install Python 3.10 or later with Tcl/Tk enabled. See README.md.
  pause
)
