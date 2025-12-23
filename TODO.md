# WC Support System - Security Fix

## Vulnerability Report
**Source**: [Wordfence Vulnerability Report](https://www.wordfence.com/threat-intel/vendor/vulnerability-report/e74fb552-3ef4-47cd-8fe6-8cc1e74b8377)

**Issue**: Missing capability checks on AJAX callback functions allowed authenticated users with Subscriber-level access to:
- Delete arbitrary support tickets
- Modify ticket status without authorization

**Affected versions**: All versions up to and including 1.2.6 (free version on wordpress.org)

---

## Security Fixes Applied (Branch: premium)

### 1. `delete_single_ticket_callback()` (line ~1691)
**File**: `includes/class-wc-support-system.php`

**Added capability check**:
- Only users with `manage_woocommerce` OR `manage_options` can delete tickets
- Returns error message for unauthorized users

**Implementation**:
```php
if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 'message' => __( 'You do not have permission to delete tickets.', 'wc-support-system' ) ) );
    exit;
}
```

---

### 2. `change_ticket_status_callback()` (line ~1138)
**File**: `includes/class-wc-support-system.php`

**Added capability check**:
- Users with `manage_woocommerce` OR `manage_options` can change any ticket status
- Regular users can ONLY change status of their own tickets (verified via `user_id`)

**Implementation**:
```php
$has_admin_capability = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );

if ( ! $has_admin_capability ) {
    $ticket = self::get_ticket( $ticket_id );
    if ( ! $ticket || (int) $ticket->user_id !== get_current_user_id() ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission to change this ticket status.', 'wc-support-system' ) ) );
        exit;
    }
}
```

---

### 3. `delete_single_thread_callback()` (line ~1632)
**File**: `includes/class-wc-support-system.php`

**Added capability check** (not in original report but fixed for consistency):
- Only users with `manage_woocommerce` OR `manage_options` can delete threads
- Returns error message for unauthorized users

**Implementation**:
```php
if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 'message' => __( 'You do not have permission to delete threads.', 'wc-support-system' ) ) );
    exit;
}
```

---

## Next Steps

### Cherry-pick to other branches:
1. **dev-version** branch (development)
2. **master** branch
3. **free** branch (for wordpress.org release)

### Version update:
- Update version number when merging to free branch
- Current version: 1.2.4
- Next secure version: 1.2.7+ (as per Wordfence report indicating ≤1.2.6 vulnerable)

### Testing checklist:
- [ ] Verify Subscriber users CANNOT delete tickets
- [ ] Verify Subscriber users CANNOT change ticket status of other users' tickets
- [ ] Verify Subscriber users CAN change status of their OWN tickets
- [ ] Verify Shop Managers CAN delete tickets
- [ ] Verify Administrators CAN delete tickets and change any ticket status
- [ ] Verify nonce validation still works
- [ ] Test AJAX error responses display correctly

---

## Files Modified
- `includes/class-wc-support-system.php` (3 security fixes applied)

## Commit Message
```
Security fix: Add capability checks to prevent unauthorized ticket/thread manipulation

- Add permission checks to delete_single_ticket_callback
- Add permission checks to change_ticket_status_callback
- Add permission checks to delete_single_thread_callback
- Fixes Wordfence vulnerability report (versions ≤1.2.6)
- Only shop managers and administrators can delete tickets/threads
- Users can only change status of their own tickets unless they have admin privileges
```
