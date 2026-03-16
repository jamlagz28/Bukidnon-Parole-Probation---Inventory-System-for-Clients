<?php
// includes/check_permissions.php

function canEdit($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canDelete($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canAdd($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canManageStaff($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function isAdmin($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}
?>