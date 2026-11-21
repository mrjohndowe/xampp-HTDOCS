# Motorlog for Windows

This GUI needs a running Motorlog PHP website, already installed and initialized on XAMPP. In that website, sign in, go to Settings, create an API token, and copy it.

Install Python 3.10 or newer from python.org with the `py` launcher and Tcl/Tk. In PowerShell open this directory, then run:

```
py -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe .\motorlog.py
```

Pillow is recommended for JPEG/PNG preview, not required for connecting, editing, exporting, or uploading. With no internet, run `py .\motorlog.py` directly. Enter a URL such as `http://localhost/motorlog/` and the website token. Test and connect checks both website onboarding and authorization. Use HTTPS away from localhost. The GUI stores connection preferences, not a second vehicle database; optional remembered credentials are encrypted by Windows for the current account.

Use Refresh (F5) to pull new changes. Print via the installed PDF viewer. Check for updates is under Settings; downloading does not install anything. Do not overwrite any server configuration or delete private workspace files when replacing the desktop release.
