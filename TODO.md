# WC Support System - Release History

## 🚧 TODO: AI Upsell per versione Free

**Branch**: `free`
**Data**: Febbraio 2026

### Obiettivo
Mostrare il pulsante "AI Suggestion" nella versione free come upsell per incentivare l'upgrade alla versione premium.

### Comportamento previsto
1. Pulsante visibile solo per admin (nel form risposta ticket)
2. Badge "PRO" accanto al testo del pulsante
3. Al clic: mostra notice con descrizione feature e link upgrade

### Implementazione (Approccio A - Solo upsell, nessun merge)
Aggiungere direttamente nel branch free senza merge dal dev-version:

**File da modificare**: `includes/class-wc-support-system.php`
- Funzione `create_new_thread()` (~linea 778): aggiungere HTML pulsante con badge PRO

**HTML da aggiungere** (prima del form, solo se `$is_admin`):
```php
<?php if ( $is_admin ) : ?>
<div class="wss-ai-container wss-ai-premium-upsell">
    <button type="button" class="button wss-ai-suggest-pro">
        <span class="dashicons dashicons-lightbulb"></span>
        <?php esc_html_e( 'AI Suggestion', 'wc-support-system' ); ?>
        <span class="wss-pro-badge">PRO</span>
    </button>
</div>
<?php endif; ?>
```

**CSS da aggiungere** in `css/wss-admin-style.css`:
```css
/* AI Upsell Button */
.wss-ai-premium-upsell {
    margin-bottom: 15px;
}
.wss-ai-suggest-pro {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.wss-pro-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
}

/* AI Upsell Modal */
.wss-ai-upsell-notice {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-left: 4px solid #667eea;
    padding: 15px;
    margin: 10px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.wss-ai-upsell-notice h4 {
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.wss-ai-upsell-notice ul {
    margin: 10px 0;
}
.wss-ai-upsell-notice li {
    margin: 5px 0;
}
```

**JavaScript** (inline in `wss_admin_scripts()` o in admin_footer):
```javascript
jQuery(document).on('click', '.wss-ai-suggest-pro', function(e) {
    e.preventDefault();
    var $container = jQuery(this).closest('.wss-ai-premium-upsell');

    if ($container.find('.wss-ai-upsell-notice').length) {
        $container.find('.wss-ai-upsell-notice').slideToggle();
        return;
    }

    var notice = '<div class="wss-ai-upsell-notice">' +
        '<h4><span class="dashicons dashicons-lightbulb"></span> AI Suggestion - Funzionalità Premium</h4>' +
        '<p>Genera risposte intelligenti ai ticket usando OpenAI, Anthropic, DeepSeek o Groq.</p>' +
        '<ul>' +
        '<li>✓ Suggerimenti basati sul contesto del ticket</li>' +
        '<li>✓ Ricerca ticket simili già risolti</li>' +
        '<li>✓ Raffina le risposte con istruzioni</li>' +
        '</ul>' +
        '<p><a href="https://www.ilghera.com/product/wc-support-system-premium/" class="button button-primary" target="_blank">Scopri la versione Premium →</a></p>' +
        '</div>';

    $container.append(notice);
});
```

### Provider testati (versione premium)
- DeepSeek: ✅ Testato
- Anthropic: Da verificare
- OpenAI: Non testato (stesso formato DeepSeek)
- Groq: Non testato (stesso formato DeepSeek)

### Note
- La versione premium su branch `dev-version` ha la funzionalità AI completa
- CLAUDE.md creato con documentazione architettura
- Analisi gestione errori completata e salvata nel TODO del branch dev-version

---

## 🚧 PENDING RELEASE - Security Fixes for Wordfence Report

### Security Fixes Applied (Awaiting Release)

Three critical security vulnerabilities have been fixed in response to Wordfence vulnerability reports:

#### 1. Fixed: `change_ticket_status_callback()` Ownership Verification
**Issue**: Previous fix used `user_id` comparison which failed for guest users (user_id = 0)
**Fix**: Now uses `user_email` comparison with proper guest user support via cookie
**Impact**: Guest users can now properly change status of their own tickets

#### 2. Added: `update_additional_recipients()` Security Check
**Issue**: Function was exposed via `wp_ajax_nopriv_` without ANY ownership verification
**Fix**: Added complete ownership verification using email-based comparison
**Impact**: Prevents unauthorized users from modifying ticket recipients

#### 3. Fixed: Guest User Cookie Validation Bypass (Wordfence Report - Feb 2026)
**Issue**: Guest user access validation relied solely on the `wss-guest-email` cookie which could be easily spoofed by attackers. An unauthenticated attacker could set a fake cookie and access any ticket by ID via the `wp_ajax_nopriv_get_ticket_content` endpoint.
**CVE**: Pending (Wordfence report: unauthorized access to `get_ticket_content_callback` in versions up to 1.2.6)

**Fix**: Created new `validate_guest_ticket_access()` method that performs complete validation:
1. Verifies both `wss-guest-email` AND `wss-order-id` cookies exist
2. Validates the order exists in WooCommerce database
3. Confirms the cookie email matches the order's billing email
4. Verifies ticket ownership matches the validated email

**Functions Updated**:
- `get_ticket_content_callback()` - Primary target of the vulnerability
- `change_ticket_status_callback()` - Also vulnerable to cookie spoofing
- `update_additional_recipients()` - Also vulnerable to cookie spoofing

**Implementation**:
```php
private function validate_guest_ticket_access( $ticket_email ) {
    if ( ! isset( $_COOKIE['wss-guest-email'], $_COOKIE['wss-order-id'] ) ) {
        return false;
    }

    $guest_email = sanitize_email( wp_unslash( $_COOKIE['wss-guest-email'] ) );
    $order_id    = sanitize_text_field( wp_unslash( $_COOKIE['wss-order-id'] ) );

    // Validate that the order exists and email matches the order's billing email.
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }

    if ( $order->get_billing_email() !== $guest_email ) {
        return false;
    }

    // Verify ticket ownership.
    return $ticket_email === $guest_email;
}
```

**Impact**: Attackers can no longer spoof guest cookies to access arbitrary tickets. The `wp_ajax_nopriv_` hooks are preserved to maintain guest user functionality, but access is now properly validated against WooCommerce order data.

**Files Modified**: `includes/class-wc-support-system.php`

**Commits**:
- `ae3c97e` - Security fix: Correct ownership verification in change_ticket_status_callback
- `93c95d8` - Security fix: Add ownership verification to update_additional_recipients
- (pending) - Security fix: Add robust guest cookie validation to prevent unauthorized ticket access

**Next Steps**: Ready for Wordfence resubmission and wordpress.org release

---

# WC Support System - Release History

## ✅ Version 1.2.9 (Free) / 1.2.7 (Premium) - 27 December 2025

### Bug Fix: Billing Email Retrieval in get_user_products()

**Issue Found**: Bug in `get_user_products()` function preventing guest users from selecting products when creating tickets.

**Root Cause**: Introduced in HPOS compatibility update (commit 307ed38, December 2023)
- Incorrect method: `$order->get_meta('_billing_email')`
- Correct method: `$order->get_billing_email()`

**Files Modified**: `includes/class-wc-support-system.php`

**Fix Applied**:
```php
// Before (BROKEN)
if ( $order->get_meta( '_billing_email' ) === $user_email ) {

// After (FIXED)
$order = wc_get_order( $order_id );
if ( ! $order ) {
    return null;
}
$order_email = $order->get_billing_email();
if ( $order_email === $user_email ) {
```

### Releases Completed

#### Premium Version 1.2.7
**Branch**: premium
**Tag**: premium-1.2.7
**Deployed to**: ilghera.com update servers

**Commits**:
- `c1b8bf0` - Bug fix: Correct billing email retrieval in get_user_products for guest users
- `118a0e3` - Release version 1.2.7 - Bug fix for guest user product selection

**Changelog**:
```
= 1.2.7 =
Release Date: 27 December 2025

    * Bug Fix: Fixed product selection for guest users when creating tickets
    * Bug Fix: Corrected billing email retrieval method in get_user_products()
    * Bug Fix: Added null check for order existence
```

#### Free Version 1.2.9
**Branch**: free
**Tag**: 1.2.9
**Deployed to**:
- wordpress.org SVN (revision 3428339)
- GitHub (master branch + tag 1.2.9)
- Bitbucket (tag 1.2.9)

**Commit**:
- `3ba9100` - Release version 1.2.9 - Bug fix for billing email retrieval

**Changelog**:
```
= 1.2.9 =
Release Date: 27 December 2025

    * Bug Fix: Corrected billing email retrieval method in get_user_products()
    * Bug Fix: Added null check for order existence
```

### Workflow Executed

```
1. Bug discovered during testing of CVE-2025-14033 fix
2. Fix applied to premium branch with debug logging
3. Testing confirmed fix works for guest users
4. Debug logging removed
5. Version updated: premium 1.2.6 → 1.2.7
6. Release to ilghera.com servers completed
7. Cherry-pick fix to dev-version branch (commit 07fb8fc)
8. Merge dev-version into master (commit 668079f)
9. Bug also found and fixed in free branch
10. Version updated: free 1.2.8 → 1.2.9
11. Release to wordpress.org SVN (rev. 3428339)
12. Push to GitHub (master + tag)
13. Push to Bitbucket (tag)
```

### All Branches Updated
- ✅ premium (1.2.7) - tag premium-1.2.7 pushed
- ✅ dev-version - cherry-picked from premium
- ✅ master - merged from dev-version
- ✅ free (1.2.9) - tag 1.2.9 pushed to all remotes

---

## ✅ Version 1.2.8 (Free) / 1.2.6 (Premium) - 27 December 2025

### Security Fix: CVE-2025-14033 - Unauthorized Ticket Content Access

**Issue**: Missing ownership verification in `get_ticket_content_callback()` allowed any authenticated user to view arbitrary ticket content by manipulating the ticket ID.

**Affected Versions**: All versions prior to 1.2.6 (premium) / 1.2.8 (free)

**Fix Applied**: Added ownership verification supporting three user types:
1. Administrators and Shop Managers (full access)
2. Logged-in users (own tickets only)
3. Guest users via cookies (own tickets only - premium feature)

**Files Modified**: `includes/class-wc-support-system.php`

**Implementation**:
```php
// Verify ownership or admin privileges
$has_access = false;

// Check if user is admin or shop manager
if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) {
    $has_access = true;
}
// Check if logged in user owns the ticket
elseif ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( $ticket->user_email === $current_user->user_email ) {
        $has_access = true;
    }
}

// Check if guest user owns the ticket (via cookie)
if ( ! $has_access && isset( $_COOKIE['wss-guest-email'] ) ) {
    $guest_email = sanitize_email( wp_unslash( $_COOKIE['wss-guest-email'] ) );
    if ( $ticket->user_email === $guest_email ) {
        $has_access = true;
    }
}

if ( ! $has_access ) {
    wp_send_json_error( array( 'message' => __( 'You do not have permission to view this ticket.', 'wc-support-system' ) ) );
    exit;
}
```

### Guest Cookie Fix
**Issue Found**: Guest user cookies not persisting after login
**Cause**: Missing cookie parameters (expire, path, domain)

**Fix Applied** in `support_access_validation()`:
```php
$expire = time() + (86400 * 30); // 30 days
setcookie( 'wss-support-access', '1', $expire, COOKIEPATH, COOKIE_DOMAIN );
setcookie( 'wss-guest-name', $guest_name, $expire, COOKIEPATH, COOKIE_DOMAIN );
setcookie( 'wss-guest-email', $email, $expire, COOKIEPATH, COOKIE_DOMAIN );
setcookie( 'wss-order-id', $order_id, $expire, COOKIEPATH, COOKIE_DOMAIN );
```

### Releases Completed

#### Premium Version 1.2.6
**Deployed to**: ilghera.com update servers
**Tag**: premium-1.2.6

#### Free Version 1.2.8
**Deployed to**: wordpress.org SVN (revision 3428106)
**Tag**: 1.2.8

### Wordfence Submission
**GitHub Compare**: https://github.com/ilGhera/wc-support-system/compare/1.2.7...1.2.8

---

# WC Support System - Security Fix v1.2.7 (CVE-2025-14034)

## Vulnerability Report
**Source**: [Wordfence Vulnerability Report](https://www.wordfence.com/threat-intel/vendor/vulnerability-report/e74fb552-3ef4-47cd-8fe6-8cc1e74b8377)

**Issue**: Missing capability checks on AJAX callback functions allowed authenticated users with Subscriber-level access to:
- Delete arbitrary support tickets
- Modify ticket status without authorization

**Affected versions**: All versions up to and including 1.2.6 (free version on wordpress.org)

---

## ✅ Security Fixes Applied

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

## ✅ Deployment Workflow Completed

### 1. Branch: **premium**
**Status**: ✅ Security fixes applied and committed

**Commits**:
- `e6cd511` - Security fix: Add capability checks to prevent unauthorized ticket/thread manipulation
- `2882676` - Update ilghera-notice submodule to v1.2.0 with singleton pattern
- `950e02d` - Nuovo richiamo ilghera-notice in main file

**Files modified**:
- `includes/class-wc-support-system.php` (3 security fixes)
- `TODO.md` (this file)

---

### 2. Branch: **dev-version**
**Status**: ✅ Cherry-picked from premium (3 commits)

**Process**:
- Cherry-pick of commits `2882676`, `e6cd511`, `950e02d` from premium
- Submodule conflict resolved (ilghera-notice already at newer version)

**Commits**:
- `840309c` - Update ilghera-notice submodule to v1.2.0 with singleton pattern
- `f1b1247` - Security fix: Add capability checks to prevent unauthorized ticket/thread manipulation
- `bf35a5c` - Nuovo richiamo ilghera-notice in main file

---

### 3. Branch: **master**
**Status**: ✅ Merged from dev-version

**Process**: Merge dev-version into master

**Note**: Tagify differences present but not touched (applied directly in premium, will be handled separately)

---

### 4. Branch: **free** (wordpress.org release)
**Status**: ✅ Merged from master + Version updated to 1.2.7

**Process**:
1. Merged master into free
2. Resolved conflicts:
   - Removed `ilghera-notice` integration (premium-only feature)
   - Kept free version of `wc-support-system.php`
3. Updated version from 1.2.6 to 1.2.7
4. Updated changelog in readme.txt

**Commits**:
- `b0498e1` - Merge master into free - Security fix for Wordfence vulnerability
- `7f478a5` - Release version 1.2.7 - Security fix

**Files modified**:
- `includes/class-wc-support-system.php` (security fixes)
- `wc-support-system.php` (version updated to 1.2.7)
- `readme.txt` (version and changelog updated)
- `TODO.md` (documentation)

**Changelog added**:
```
= 1.2.7 =
Release Date: 23 December 2025

    * Security: Fixed missing capability checks on AJAX callbacks
    * Security: Prevent unauthorized users from deleting or modifying tickets
```

---

## 📋 Testing Checklist

Before pushing to production:

- [ ] Verify Subscriber users CANNOT delete tickets
- [ ] Verify Subscriber users CANNOT change ticket status of other users' tickets
- [ ] Verify Subscriber users CAN change status of their OWN tickets
- [ ] Verify Shop Managers CAN delete tickets
- [ ] Verify Administrators CAN delete tickets and change any ticket status
- [ ] Verify nonce validation still works
- [ ] Test AJAX error responses display correctly

---

## 🚀 Next Steps for Release

### To push changes:
```bash
# Push premium branch
git checkout premium
git push origin premium

# Push dev-version branch
git checkout dev-version
git push origin dev-version

# Push master branch
git checkout master
git push origin master

# Push free branch (wordpress.org)
git checkout free
git push origin free
```

### To release on wordpress.org:
1. Push `free` branch to SVN repository
2. Tag the release as `1.2.7`
3. Update trunk and tags in WordPress.org SVN
4. Verify the release appears on wordpress.org plugin page

---

## 📝 Notes

- **Tagify submodule**: Has manual modifications (rollback) that must be preserved across all branches
- **ilghera-notice**: Only present in premium branch, excluded from free version
- **Version numbers**:
  - Premium: Still at 1.2.4 (to be updated later)
  - Free: Updated to 1.2.7 (ready for release)

---

## 🔮 Future Improvements

Consider creating:
- Automated release script for version updates across all files
- Automated testing script for capability checks
- CI/CD workflow for security testing
