<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

admin_logout();
redirect('/HIRA/admin/index.php');
