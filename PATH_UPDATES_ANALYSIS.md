# Path Updates Analysis - Directory Restructuring

**Generated:** 2026-03-31  
**Status:** All include/require statements and header redirects identified

---

## Summary
- **Total Files Analyzed:** 34 files with includes/requires
- **Total Header Redirects:** 71 instances
- **Files Requiring Updates:** All files with relative path includes

---

## Detailed Updates by File

### PUBLIC FOLDER (Entry Points)

#### 1. **public/dashboard.php**
**Current Location:** `public/dashboard.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Current Location Issues:**
- This file references `config/database.php` relatively, but from `public/` folder

**Corrected Include:**
- `include __DIR__ . '/../config/database.php';` (Go up one level to root, then to config/)

**Header Location Redirects (Line 7):**
- `header("Location: login.php");` → ✓ OK (same folder - public/)

**New Path:** `../../config/database.php` OR use `__DIR__`

---

#### 2. **public/login.php**
**Current Location:** `public/login.php`  
**Header Location Redirect (Line 27):**
- `header("Location: dashboard.php");` → ✓ OK (same folder - public/)

**Status:** No path updates needed

---

#### 3. **public/logout.php**
**Current Location:** `public/logout.php`  
**Header Location Redirect (Line 4):**
- `header("Location: index.php");` → ✓ OK (same folder - public/)

**Status:** No path updates needed

---

#### 4. **public/register.php**
**Current Location:** `public/register.php`  
**Status:** No includes/redirects found

---

#### 5. **public/index.php**
**Current Location:** `public/index.php`  
**Status:** No includes/redirects found

---

### APP/VIEWS/CLIENTS FOLDER

#### 6. **app/views/clients/clients.php**
**Current Location:** `app/views/clients/clients.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';` (Go up 2 levels: clients → views → app, then to root config/)

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 29: `header("Location: clients.php?msg=deleted");` → ✓ OK (same folder)

---

#### 7. **app/views/clients/client_details.php**
**Current Location:** `app/views/clients/client_details.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 18: `header("Location: dashboard.php");` → Should be `../../../public/dashboard.php`

---

#### 8. **app/views/clients/add_client.php**
**Current Location:** `app/views/clients/add_client.php`  
**Current Includes:**
- Line 2: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Status:** Verify any header redirects in file

---

#### 9. **app/views/clients/add_client_with_pi.php**
**Current Location:** `app/views/clients/add_client_with_pi.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 16: `header("Location: dashboard.php?error=unauthorized");` → Should be `../../../public/dashboard.php?error=unauthorized`
- Line 65: `header("Location: clients.php?msg=added_with_pi");` → ✓ OK (same folder)

---

#### 10. **app/views/clients/delete_client.php**
**Current Location:** `app/views/clients/delete_client.php`  
**Current Includes:**
- Line 2: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

---

#### 11. **app/views/clients/view_client.php**
**Current Location:** `app/views/clients/view_client.php`  
**Current Includes:**
- Line 3: `include(__DIR__ . "/config/database.php");` ⚠️ BROKEN - Uses __DIR__ but wrong path

**Corrected Include:**
- `include(__DIR__ . "/../../config/database.php");`

**Header Location Redirects:**
- Line 6: `header("Location: index.php");` → Should be `../../../public/index.php`
- Line 12: `header("Location: dashboard.php");` → Should be `../../../public/dashboard.php`

---

#### 12. **app/views/clients/add_investigation.php**
**Current Location:** `app/views/clients/add_investigation.php`  
**Current Includes:**
- Line 3: `include(__DIR__ . "/../config/database.php");` ⚠️ BROKEN - Goes up 1 level only

**Corrected Include:**
- `include(__DIR__ . "/../../config/database.php");`

**Header Location Redirects:**
- Line 6: `header("Location: ../index.php");` ⚠️ NEEDS UPDATE - Should be `../../../public/index.php`

**Other Includes Found:**
- Lines 160-161: Conditional includes for `sidebar.php` and `header.php` (check if these files exist)

---

### APP/VIEWS/PRE_INVESTIGATION FOLDER

#### 13. **app/views/pre_investigation/pre_investigation.php**
**Current Location:** `app/views/pre_investigation/pre_investigation.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 40: `header("Location: pre_investigation.php?msg=added");` → ✓ OK (same file)

---

#### 14. **app/views/pre_investigation/pi_list.php**
**Current Location:** `app/views/pre_investigation/pi_list.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 8: `header("Location: login.php");` → Should be `../../../public/login.php`

---

#### 15. **app/views/pre_investigation/pi_view.php**
**Current Location:** `app/views/pre_investigation/pi_view.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 18: `header("Location: pi_list.php");` → ✓ OK (same folder)

---

#### 16. **app/views/pre_investigation/pi_add.php**
**Current Location:** `app/views/pre_investigation/pi_add.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 8: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 21: `header("Location: pi_list.php?error=unauthorized");` → ✓ OK (same folder)
- Line 48: `header("Location: pi_list.php?msg=added");` → ✓ OK (same folder)

---

#### 17. **app/views/pre_investigation/pi_edit.php**
**Current Location:** `app/views/pre_investigation/pi_edit.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 8: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 21, 27, 35, 70: Redirect to `pi_list.php` → ✓ OK (same folder)

---

#### 18. **app/views/pre_investigation/pi_delete.php**
**Current Location:** `app/views/pre_investigation/pi_delete.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 8: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 19, 25, 34, 42, 44: Redirect to `pi_list.php` → ✓ OK (same folder)

---

#### 19. **app/views/pre_investigation/approve_pi_to_ps.php**
**Current Location:** `app/views/pre_investigation/approve_pi_to_ps.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`
- Line 4: `include 'includes/permissions.php';`

**Corrected Includes:**
- `include __DIR__ . '/../../config/database.php';`
- `include __DIR__ . '/../../includes/permissions.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 15, 20, 29: Redirect to `pi_list.php` → ✓ OK (same folder)
- Line 75: `header("Location: ps_list.php?msg=created_from_pi");` → Should be `../probation_supervision/ps_list.php?msg=created_from_pi`

---

### APP/VIEWS/PROBATION_SUPERVISION FOLDER

#### 20. **app/views/probation_supervision/ps_list.php**
**Current Location:** `app/views/probation_supervision/ps_list.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 28: `header("Location: ps_list.php");` → ✓ OK (same file)

---

#### 21. **app/views/probation_supervision/ps_view.php**
**Current Location:** `app/views/probation_supervision/ps_view.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 18: `header("Location: ps_list.php");` → ✓ OK (same folder)

---

#### 22. **app/views/probation_supervision/ps_add.php**
**Current Location:** `app/views/probation_supervision/ps_add.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 17: `header("Location: ps_list.php?error=unauthorized");` → ✓ OK (same folder)
- Line 96: `header("Location: client_details.php?id=$client_id&msg=ps_added");` → Should be `../clients/client_details.php?id=$client_id&msg=ps_added`

---

#### 23. **app/views/probation_supervision/ps_payment.php**
**Current Location:** `app/views/probation_supervision/ps_payment.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 17, 23, 32: Redirect to `ps_list.php` → ✓ OK (same folder)

---

### APP/VIEWS/STAFF FOLDER

#### 24. **app/views/staff/staff_management.php**
**Current Location:** `app/views/staff/staff_management.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 53, 67: Redirect to `staff_management.php` → ✓ OK (same file)

---

#### 25. **app/views/staff/profile.php**
**Current Location:** `app/views/staff/profile.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`

---

#### 26. **app/views/staff/changepassword.php**
**Current Location:** `app/views/staff/changepassword.php`  
**Current Includes:**
- Line 2: `include("config/database.php");`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

---

#### 27. **app/views/staff/add_probationer.php**
**Current Location:** `app/views/staff/add_probationer.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 80, 101, 136: Redirect to `clients.php` → Should be `../clients/clients.php`

---

#### 28. **app/views/staff/edit_probationer.php**
**Current Location:** `app/views/staff/edit_probationer.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 16, 24, 57: Redirect to `clients.php` → Should be `../clients/clients.php`

---

### APP/VIEWS/REPORTS FOLDER

#### 29. **app/views/reports/monthly_reports.php**
**Current Location:** `app/views/reports/monthly_reports.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`
- Line 41: `header("Location: monthly_reports.php?msg=...` → ✓ OK (same file)

---

#### 30. **app/views/reports/view_reports.php**
**Current Location:** `app/views/reports/view_reports.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 7: `header("Location: login.php");` → Should be `../../../public/login.php`
- Lines 13, 22: Redirect to `clients.php` → Should be `../clients/clients.php`

---

#### 31. **app/views/reports/activity_logs.php**
**Current Location:** `app/views/reports/activity_logs.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: dashboard.php");` → Should be `../../../public/dashboard.php`

---

### APP/VIEWS/SEARCH FOLDER

#### 32. **app/views/search/search.php**
**Current Location:** `app/views/search/search.php`  
**Current Includes:**
- Line 3: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

**Header Location Redirects:**
- Line 6: `header("Location: login.php");` → Should be `../../../public/login.php`

---

#### 33. **app/views/search/search_live.php**
**Current Location:** `app/views/search/search_live.php`  
**Current Includes:**
- Line 2: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

---

#### 34. **app/views/search/live_search.php**
**Current Location:** `app/views/search/live_search.php`  
**Current Includes:**
- Line 2: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

---

### APP/ACTIONS FOLDER

#### 35. **app/actions/update_status.php**
**Current Location:** `app/actions/update_status.php`  
**Current Includes:**
- Line 2: `include 'config/database.php';`

**Corrected Include:**
- `include __DIR__ . '/../../config/database.php';`

---

### CONFIG FOLDER

#### 36. **config/session.php**
**Current Location:** `config/session.php`  
**Header Location Redirects:**
- Line 22: `header("Location: login.php");` → Should be `public/login.php` (or absolute path)

---

## SUMMARY TABLE: Files Needing Updates

| File | Location | Include Updates Needed | Redirect Updates Needed | Priority |
|------|----------|------------------------|-------------------------|----------|
| public/dashboard.php | public/ | 1 | 0 | HIGH |
| app/views/clients/*.php | app/views/clients/ | 1-2 ea. | 1-3 ea. | HIGH |
| app/views/pre_investigation/*.php | app/views/pre_investigation/ | 1-2 ea. | 1-4 ea. | HIGH |
| app/views/probation_supervision/*.php | app/views/probation_supervision/ | 1 ea. | 1-3 ea. | HIGH |
| app/views/staff/*.php | app/views/staff/ | 1 ea. | 1-3 ea. | HIGH |
| app/views/reports/*.php | app/views/reports/ | 1 ea. | 1-2 ea. | MEDIUM |
| app/views/search/*.php | app/views/search/ | 1 ea. | 0-1 ea. | MEDIUM |
| app/actions/update_status.php | app/actions/ | 1 | 0 | MEDIUM |
| config/session.php | config/ | 0 | 1 | LOW |

---

## CRITICAL NOTES

1. **Path Format:** Use `__DIR__` for absolute paths - more reliable than relative paths
2. **Cross-folder Redirects:** Files redirecting between different view folders need `../` to navigate correctly
3. **Public Files:** Redirects from app/views/ to public/ files need `../../../public/filename.php`
4. **Same-folder Redirects:** These can stay as relative file references (e.g., `pi_list.php`)
5. **Test After Updates:** All path changes should be tested to ensure files load correctly

---

