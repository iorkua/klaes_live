 

## ✅ Final Structured Requirements (with Table References)

### 1. Remove Unnecessary Validation

* **Page No.**, **Serial No.**, **Volume No.** → not required anymore.
* If left empty, insert as **0**.
* `regNo` should default to `0/0/0`.

---

### 2. Add New Input Field

* **Grantor** → must be added across the 3 tables.

---

### 3. File Number Search Logic

When the user selects or enters a **file number**, the system must search across:

#### a) `property_records`

```sql
[mlsFNo],
[kangisFileNo],
[NewKANGISFileno],
[transaction_type]        -- use as Instrument Type
[transaction_date],
[serialNo],
[pageNo],
[volumeNo],
[regNo],
[Grantor],
[Grantee],
[property_description]    -- use as Location
[is_caveated],
[caveated_comment],
[caveat_id]
```

#### b) `registered_instruments`

```sql
[MLSFileNo],
[KAGISFileNO],
[NewKANGISFileNo],
[property_description]    -- Location
[particularsRegistrationNumber],
[volume_no],
[page_no],
[serial_no],
[instrument_type],
[Grantor],
[Grantee],
[is_caveated],
[caveated_comment]
```

#### c) `CofO`

```sql
[mlsFNo],
[kangisFileNo],
[NewKANGISFileno],
[transaction_type]        -- use as Instrument Type
[transaction_date],
[serialNo],
[pageNo],
[volumeNo],
[regNo],
[Grantor],
[Grantee],
[property_description]    -- use as Location
[is_caveated],
[caveated_comment],
[caveat_id]
```

---

### 4. If File Number Not Found

* A new record must be **created automatically** in the **`property_records`** table with defaults:

  ```sql
  serialNo = 0,
  pageNo = 0,
  volumeNo = 0,
  regNo = '0/0/0'
  ```
* No error message — instead, allow the user to save this new record.

---

### 5. Button State Logic (UI)

* **Save record to database button**

  * Disabled & greyed out by default.
  * Enabled only if the file number **is not found** (to allow creating a new record).

* **Place Caveat button**

  * Enabled if the file number **exists** in any of the 3 tables.
  * Disabled & greyed out if not found.
  * Once the user saves the new record into `property_records`, the **Place Caveat** button becomes enabled.

---

### 6. Example Buttons (with default states)

```html
<!-- Save record button (disabled by default) -->
<button id="save-draft" class="px-4 py-2 border rounded-md flex items-center bg-gray-300 text-gray-600 cursor-not-allowed" disabled>
    <i class="fa-regular fa-floppy-disk mr-2"></i>
    Save record to database
</button>

<!-- Place caveat button -->
<button id="place-caveat" class="px-4 py-2 bg-blue-600 text-white rounded-md flex items-center">
    <i class="fa-regular fa-paper-plane mr-2"></i>
    Place Caveat
</button>
```

JS will handle toggling these depending on whether the file number exists in the 3 tables.

---

⚡ This way:

* We **search all 3 tables** first.
* If found → fetch details, enable **Place Caveat**.
* If not found → enable **Save Record**, insert into `property_records`, then allow **Place Caveat**.

---
 