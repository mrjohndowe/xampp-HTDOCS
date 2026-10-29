<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
if (!ic_installed()) { header('Location: install/'); exit; }
ic_render();
