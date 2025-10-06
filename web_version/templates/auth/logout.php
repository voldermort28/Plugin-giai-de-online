<?php
// web_version/templates/auth/logout.php

$auth->logout();
set_message('success', 'Bạn đã đăng xuất.');
redirect('/login');