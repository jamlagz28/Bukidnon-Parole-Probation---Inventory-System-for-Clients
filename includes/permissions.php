<?php
// includes/permissions.php

/**
 * Check if user can edit records
 * @param string $user_role The user's role (main, admin, staff)
 * @return boolean True if user can edit
 */
function canEdit($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

/**
 * Check if user can add records
 * @param string $user_role The user's role
 * @return boolean True if user can add
 */
function canAdd($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

/**
 * Check if user can delete records
 * @param string $user_role The user's role
 * @return boolean True if user can delete
 */
function canDelete($user_role) {
    return ($user_role == 'main' || $user_role == 'admin');
}

/**
 * Check if user can manage staff
 * @param string $user_role The user's role
 * @return boolean True if user can manage staff
 */
function canManageStaff($user_role) {
    return ($user_role == 'main'); // Only main admin can manage staff
}

/**
 * Get access level description
 * @param string $user_role The user's role
 * @return string Description of access level
 */
function getAccessLevel($user_role) {
    if($user_role == 'main') return 'Full Access (Main Admin)';
    if($user_role == 'admin') return 'Full Access (Admin)';
    return 'View Only';
}

/**
 * Check if user is in view-only mode
 * @param string $user_role The user's role
 * @return boolean True if user is view only
 */
function isViewOnly($user_role) {
    return ($user_role == 'staff');
}

/**
 * Get role badge color
 * @param string $user_role The user's role
 * @return string CSS class for role badge
 */
function getRoleBadgeClass($user_role) {
    if($user_role == 'main') return 'role-main';
    if($user_role == 'admin') return 'role-admin';
    return 'role-staff';
}

/**
 * Get role icon
 * @param string $user_role The user's role
 * @return string Font Awesome icon class
 */
function getRoleIcon($user_role) {
    if($user_role == 'main') return 'fa-crown';
    if($user_role == 'admin') return 'fa-shield-alt';
    return 'fa-user';
}
?>