<?php
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function check_admin() {
    check_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 0) {
        header('Location: ../user/index.php');
        exit;
    }
}
