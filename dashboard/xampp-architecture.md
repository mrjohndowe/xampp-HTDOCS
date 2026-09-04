A sensible architecture would be:
```
Windows
│
├── XAMPP / Apache
│   ├── PHP sites
│   ├── Laravel
│   ├── MariaDB
│   └── phpMyAdmin
│
├── Node/.NET applications
│   ├── :3080
│   ├── :8888
│   └── other ports
│
└── Apache reverse proxy
    ├── app.example.com → localhost:3080
    └── service.example.com → localhost:8888
```
That matches your existing environment much better than migrating everything to IIS merely because Windows included another web server and apparently felt lonely.

---
Use this as your corrected baseline:
```
B:\xampp\
├── apache\
├── htdocs\
├── mysql\
├── php\
├── php-8.2-backup\
└── phpMyAdmin\
```
For PHP 8.5.9, extract it to: `B:\xampp\php`