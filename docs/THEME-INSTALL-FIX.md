# Theme install error: "missing the style.css stylesheet"

## Most common mistake (your error)

You uploaded **`FOR-HOSTINGER-UPLOAD.zip`** — the **whole folder** compressed into one zip.

That folder contains multiple files. WordPress expects **one theme** with `style.css` at the **top level** of the zip. There is no `style.css` in `FOR-HOSTINGER-UPLOAD.zip`, so it fails.

### Fix in 30 seconds

1. Open File Explorer: `D:\jwellery ecommerce\FOR-HOSTINGER-UPLOAD`
2. **Do not** right-click the folder → "Compress" / "Send to ZIP"
3. Upload **only this file** for the theme:

   **`1-THEME-UPLOAD-jwellery-jewelry.zip`**

   **Do NOT upload `*-FLAT.zip`** — WordPress installs it as a folder named after the zip file (e.g. `1-THEME-UPLOAD-jwellery-jewelry-FLAT`), and updates will not apply correctly.

4. WordPress path: **Appearance → Themes → Add New → Upload Theme**

5. Later upload **`2-PLUGIN-UPLOAD-jewelry-upi-store.zip`** under **Plugins → Upload**

---

## Wrong vs correct uploads

| File you uploaded | Result |
|-------------------|--------|
| `FOR-HOSTINGER-UPLOAD.zip` | FAIL — folder zipped |
| `jwellery ecommerce.zip` | FAIL — project folder |
| `jwellery ecommerce (2).zip` | FAIL — project folder |
| **`1-THEME-UPLOAD-jwellery-jewelry-FLAT.zip`** | **BAD** — wrong folder name on server |
| **`1-THEME-UPLOAD-jwellery-jewelry.zip`** | **OK** — installs as `jwellery-jewelry` |
| `wordpress-theme` folder zipped | FAIL — too many nested folders |
| **`2-PLUGIN-UPLOAD-jewelry-upi-store.zip`** | OK — plugin (Plugins page, not Themes) |

---

## Visual: what WordPress expects

**Correct theme zip (either format works):**

```
FLAT zip (recommended):
style.css
functions.php
header.php
...

OR nested zip:
jwellery-jewelry/
├── style.css
├── functions.php
└── ...
```

**Your wrong zip structure:**

```
FOR-HOSTINGER-UPLOAD.zip
└── FOR-HOSTINGER-UPLOAD/
    ├── 1-THEME-UPLOAD-jwellery-jewelry.zip   <-- nested, not read as theme
    ├── 2-PLUGIN-UPLOAD-jewelry-upi-store.zip
    └── README...
```

---

## Regenerate files on your PC

```powershell
cd "D:\jwellery ecommerce"
.\scripts\prepare-hostinger-upload.ps1
```

Then open `FOR-HOSTINGER-UPLOAD` and upload **`1-THEME-UPLOAD-jwellery-jewelry.zip`** only.

---

## Install order on Hostinger

1. WordPress (hPanel installer)
2. WooCommerce plugin (search install — not upload folder)
3. **Theme:** `1-THEME-UPLOAD-jwellery-jewelry.zip`
4. **Plugin:** `2-PLUGIN-UPLOAD-jewelry-upi-store.zip`
5. WooCommerce → Payments → Manual UPI → your UPI ID

See [HOSTINGER-INSTALL.md](HOSTINGER-INSTALL.md).
