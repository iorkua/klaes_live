---
mode: agent
---
 # Task in resources\views\pagetyping\index.blade.php
first read   resources\views\pagetyping\booklet-Logic.html
Implement Booklet Management in the Page Typing module.  
This feature allows grouping multiple pages under a single document (e.g., Power of Attorney), using sequential alphabetic suffixes (`01a`, `01b`, `01c`) instead of numeric-only serial numbers.

# Requirements

## State Variables
Add the following state properties:
- bookletMode (Boolean, default = false)  
- currentBooklet (String | null, default = null)  
- bookletStartPage (String | null, default = null)  
- bookletPages (Object, default = {})  
- bookletCounter (String, default = 'a')  

## Functions
1. **startBooklet()**  
   - Triggered by “Start Booklet” button.  
   - Sets `bookletMode = true`.  
   - Generates unique `currentBooklet` ID.  
   - Uses current serial number as `bookletStartPage`.  
   - Resets `bookletCounter = 'a'`.

2. **endBooklet()**  
   - Triggered by “End Booklet” button.  
   - Disables booklet mode.  
   - Resets `bookletStartPage` and `bookletCounter`.  
   - Increments `serialNo` for the next non-booklet page.  

3. **processPage()** (modified)  
   - If booklet mode is active → append alphabetic suffix (`01a`, `01b`, etc.) to serial number.  
   - Generate `pageCode = {FileNo}-{PageType}-{PageSubType}-{Serial}`.  
   - Track booklet pages in `bookletPages[currentBooklet]`.  
   - Increment `bookletCounter` alphabetically (`a → b → c`).  
   - If not in booklet mode → normal serial increment (01, 02, 03).  

## UI Components
- Add a **Booklet Management Section** to the Page Typing UI:  
  - Button: **Start Booklet** (visible when not in booklet mode).  
  - Button: **End Booklet** (visible when booklet mode is active).  
  - Display active booklet info: e.g., *Pages 01a, 01b, 01c...*.  
  - Serial number input should update automatically:  
    - Normal Mode → numeric (01, 02, 03).  
    - Booklet Mode → auto-generated with suffix (01a, 01b, etc.).  

## Event Listeners
- `.start-booklet` → calls `startBooklet()`  
- `.end-booklet` → calls `endBooklet()`  
- Page typing flow uses updated `processPage()`  

## Integration Steps
1. Add state variables.  
2. Implement booklet functions.  
3. Update UI components (Booklet Management + Serial Input).  
4. Add event listeners.  
5. Test with scenarios:  
   - Normal typing.  
   - Start + process multiple booklet pages.  
   - End booklet → resume normal numbering.  

# Acceptance Criteria
- System correctly generates `01a, 01b, 01c...` during booklet typing.  
- Exiting booklet resumes numeric sequence (e.g., 02, 03...).  
- Booklet pages are stored in state and database with proper codes.  
- UI clearly indicates booklet mode and next expected serial.  
