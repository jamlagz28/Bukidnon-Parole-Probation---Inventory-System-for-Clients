<?php
// includes/permissions.php

function canEdit($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canAdd($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canDelete($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function canManageStaff($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

function isViewOnly($user_role) {
    return ($user_role == 'staff');
}

function getAccessLevel($user_role) {
    if($user_role == 'main') return 'Full Access (Main Admin)';
    if($user_role == 'admin') return 'Full Access (Admin)';
    return 'View Only';
}
?>