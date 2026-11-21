<?php
// EXAMPLE ONLY. Never place a real config in the public project directory.
// Setup writes the actual file to VT_PRIVATE (outside htdocs).
return [
 'dsn'=>'sqlite:C:/xampp/vehicle-tracker-private-EXAMPLE/vehicles.sqlite',
 // MySQL alternate: mysql:host=localhost;dbname=vehicle_tracker;charset=utf8mb4
 'db_user'=>null,
 'db_password'=>null,
 'site_name'=>'My Garage', 'currency'=>'USD', 'unit'=>'mi',
 'github_owner'=>'', 'github_repo'=>''
];
