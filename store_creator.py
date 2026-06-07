import pandas as pd
import zipfile
import os
from PIL import Image, ImageDraw

# ---------------------------------------------------------
# 1. DEFINE STORE DATA
# ---------------------------------------------------------
stores_data = [
    {"ref": "store_1", "name": "ElectroWorld", "cat": "electronics", "email": "contact@electroworld.eg"},
    {"ref": "store_2", "name": "Chic Style Boutique", "cat": "fashion-clothing", "email": "hello@chicstyle.eg"},
    {"ref": "store_3", "name": "Fresh Bites Cafe", "cat": "food-beverages", "email": "info@freshbites.eg"},
    {"ref": "store_4", "name": "Green Thumb Nursery", "cat": "home-garden", "email": "sales@greenthumb.eg"},
    {"ref": "store_5", "name": "Glow & Go Cosmetics", "cat": "beauty-health", "email": "support@glowandgo.eg"},
    {"ref": "store_6", "name": "Peak Performance", "cat": "sports-outdoors", "email": "team@peakperformance.eg"},
    {"ref": "store_7", "name": "Page Turner Books", "cat": "books-media", "email": "hello@pageturner.eg"},
    {"ref": "store_8", "name": "Playtime Wonder", "cat": "toys-games", "email": "fun@playtimewonder.eg"},
    {"ref": "store_9", "name": "Auto Parts Pro", "cat": "automotive", "email": "service@autopartspro.eg"},
    {"ref": "store_10", "name": "Sparkle Jewelers", "cat": "jewelry-accessories", "email": "diamonds@sparkle.eg"},
    {"ref": "store_11", "name": "Paws & Claws", "cat": "pet-supplies", "email": "woof@pawsandclaws.eg"},
    {"ref": "store_12", "name": "Desk Essentials", "cat": "office-supplies", "email": "supply@deskessentials.eg"},
    {"ref": "store_13", "name": "Tiny Tots Boutique", "cat": "baby-kids", "email": "kids@tinytots.eg"},
    {"ref": "store_14", "name": "Modern Living", "cat": "furniture", "email": "design@modernliving.eg"},
    {"ref": "store_15", "name": "Daily Fresh Groceries", "cat": "grocery", "email": "fresh@dailygroceries.eg"},
]

staff_emails = [
    "staff4@example.com", "staff5@example.com", 
    "staff3@example.com", "staff1@example.com", "staff2@example.com"
]

# ---------------------------------------------------------
# 2. BUILD DATAFRAMES FOR EXCEL SHEETS
# ---------------------------------------------------------

# Sheet 1: Stores
stores_rows = []
for i, s in enumerate(stores_data):
    stores_rows.append({
        "reference_id": s["ref"],
        "name": s["name"],
        "description": f"The best destination for {s['cat']}.",
        "email": s["email"],
        "phone": f"+201000000{i:02d}",
        "tax_id": f"TAX-9988-{i:02d}",
        "commission_rate": "0.1000",
        "status": "pending",
        "owner_email": staff_emails[i % 5], # Distribute owners among staff
        "categories": s["cat"],
        "logo_image": f"logo_{s['ref']}.png",
        "banner_image": f"banner_{s['ref']}.jpg"
    })
df_stores = pd.DataFrame(stores_rows)

# Sheet 2: Branches
branches_rows = []
for i, s in enumerate(stores_data):
    branches_rows.append({
        "reference_id": f"branch_{i+1}",
        "store_reference_id": s["ref"],
        "first_name": "Branch",
        "last_name": "Manager",
        "phone_number": f"+201100000{i:02d}",
        "address_line1": f"{i+10} Main Street",
        "address_line2": "Suite 1",
        "city": "Faiyum",
        "state_province": "Faiyum Governorate",
        "postal_code": "63511",
        "country_code": "EG",
        "latitude": "29.3084",
        "longitude": "30.8428"
    })
df_branches = pd.DataFrame(branches_rows)

# Sheet 3: Employees
employees_rows = []
for i, s in enumerate(stores_data):
    employees_rows.append({
        "store_reference_id": s["ref"],
        "branch_reference_id": f"branch_{i+1}",
        "user_email": staff_emails[i % 5], # Cycle through provided staff emails
        "role": "store_manager",
        "permissions": "claims:manage,orders:view"
    })
df_employees = pd.DataFrame(employees_rows)

# Sheet 4: Hours
hours_rows = []
for i, s in enumerate(stores_data):
    for day in range(7): # 0 (Sun) to 6 (Sat)
        is_closed = 1 if day == 5 else 0 # Closed on Fridays (Day 5)
        hours_rows.append({
            "store_reference_id": s["ref"],
            "day_of_week": day,
            "open_time": "" if is_closed else "09:00",
            "close_time": "" if is_closed else "22:00",
            "is_closed": is_closed
        })
df_hours = pd.DataFrame(hours_rows)

# ---------------------------------------------------------
# 3. GENERATE FILES & ZIP ARCHIVE
# ---------------------------------------------------------
os.makedirs("import_temp/images", exist_ok=True)

# Generate Excel File
excel_path = "import_temp/data.xlsx"
with pd.ExcelWriter(excel_path, engine='xlsxwriter') as writer:
    df_stores.to_excel(writer, sheet_name="Stores", index=False)
    df_branches.to_excel(writer, sheet_name="Branches", index=False)
    df_employees.to_excel(writer, sheet_name="Employees", index=False)
    df_hours.to_excel(writer, sheet_name="Hours", index=False)

# Generate Dummy Images (Logos & Banners)
def create_image(filename, text, size, color):
    img = Image.new('RGB', size, color=color)
    draw = ImageDraw.Draw(img)
    draw.text((size[0]//4, size[1]//2), text, fill=(255,255,255))
    img.save(filename)

for s in stores_data:
    # 500x500 PNG for Logo
    logo_path = f"import_temp/images/logo_{s['ref']}.png"
    create_image(logo_path, f"{s['name']}\nLogo", (500, 500), (45, 55, 72))
    
    # 1200x400 JPG for Banner
    banner_path = f"import_temp/images/banner_{s['ref']}.jpg"
    create_image(banner_path, f"{s['name']}\nBanner", (1200, 400), (90, 110, 140))

# Zip Everything Up
zip_filename = "my_import.zip"
with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
    # Add excel file
    zipf.write(excel_path, arcname="data.xlsx")
    
    # Add images
    for root, dirs, files in os.walk("import_temp/images"):
        for file in files:
            file_path = os.path.join(root, file)
            # Maintain the 'images/' folder structure inside the zip
            zipf.write(file_path, arcname=f"images/{file}")

# Clean up temp folder
for f in os.listdir("import_temp/images"):
    os.remove(os.path.join("import_temp/images", f))
os.rmdir("import_temp/images")
os.remove("import_temp/data.xlsx")
os.rmdir("import_temp")

print(f"Success! {zip_filename} has been created in your current directory.")