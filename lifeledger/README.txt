LifeLedger v1.0.0 — Stock XAMPP PHP Edition
================================================
1. Start Apache and MySQL in XAMPP Control Panel.
2. Copy this 'lifeledger' folder into C:\xampp\htdocs\lifeledger
3. Open http://localhost/lifeledger/ in your browser.
   Existing users can update the application files and sign in with their current password. Keep config, data, uploads, backups and storage.
4. Complete the First-Run Onboarding Wizard (no build step, no Composer, no CLI required).

CLI Equivalents (via C:\xampp\php\php.exe):
  php bin/lifeledger config:check
  php bin/lifeledger config:show
  php bin/lifeledger db:migrate
  php bin/lifeledger db:seed
  php bin/lifeledger db:verify
  php bin/lifeledger sync:drain
  php bin/lifeledger db:backup
  php bin/lifeledger db:restore <timestamp>
