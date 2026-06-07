import pandas as pd
import os
import shutil
import zipfile
import random
import string
import requests
import base64

# --- Configuration ---
TOTAL_ZIPS = 15          
PRODUCTS_PER_ZIP = 15

# --- Free, High-Quality Unsplash CDN Links ---
IMAGE_SOURCES = {
    "smartphones": [
        "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1598327105666-5b89351aff97?q=80&w=600&auto=format&fit=crop"  
    ],
    "audio": [
        "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?q=80&w=600&auto=format&fit=crop"  
    ],
    "men-fashion": [
        "https://images.unsplash.com/photo-1617137968427-85924c800a22?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1542272604-787c3835535d?q=80&w=600&auto=format&fit=crop"  
    ],
    "women-fashion": [
        "https://images.unsplash.com/photo-1515347619111-e77c44db6064?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1584917865442-de89df76afd3?q=80&w=600&auto=format&fit=crop"  
    ],
    "coffee-desserts": [
        "https://images.unsplash.com/photo-1559525839-b184a4d698c7?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1551024506-0bccd828d307?q=80&w=600&auto=format&fit=crop"  
    ],
    "beauty-health": [
        "https://images.unsplash.com/photo-1620916566398-39f1143ab7be?q=80&w=600&auto=format&fit=crop", 
        "https://images.unsplash.com/photo-1556228578-0d85b1a4d571?q=80&w=600&auto=format&fit=crop"  
    ]
}

# --- Realistic Mock Database ---
MOCK_DB = {
    "smartphones": {
        "names": ["Pro Max", "Ultra 5G", "Lite Fold", "Z Flip", "Neo Plus"],
        "brands": ["Samsung", "Apple", "Google", "Xiaomi", "OnePlus"],
        "attributes": {"Color": ["Phantom Black", "Titanium", "Snow White", "Mint"], "Storage": ["128GB", "256GB", "512GB"]},
        "price_range": (15000, 55000), "parent_cat": "electronics"
    },
    "audio": {
        "names": ["Noise Cancelling Headphones", "Wireless Earbuds", "Bluetooth Speaker", "Studio Monitors"],
        "brands": ["Sonos", "Bose", "JBL", "Sony", "Sennheiser"],
        "attributes": {"Color": ["Matte Black", "Silver", "Midnight Blue"], "Style": ["Over-Ear", "In-Ear"]},
        "price_range": (2000, 14000), "parent_cat": "electronics"
    },
    "men-fashion": {
        "names": ["Oxford Shirt", "Slim Fit Denim", "Chino Pants", "Polo T-Shirt", "Bomber Jacket"],
        "brands": ["Polo", "Levi", "Zara", "H&M", "Tommy Hilfiger"],
        "attributes": {"Size": ["S", "M", "L", "XL"], "Color": ["Navy", "Olive", "Black", "White"]},
        "price_range": (500, 3500), "parent_cat": "fashion"
    },
    "women-fashion": {
        "names": ["Floral Summer Dress", "High-Waist Jeans", "Leather Handbag", "Silk Blouse"],
        "brands": ["Zara", "Mango", "Gucci", "Prada", "H&M"],
        "attributes": {"Size": ["XS", "S", "M", "L"], "Color": ["Red", "Beige", "Black", "Pastel Pink"]},
        "price_range": (600, 5000), "parent_cat": "fashion"
    },
    "coffee-desserts": {
        "names": ["Arabica Beans", "Espresso Roast", "Chocolate Truffles", "Macarons Box", "Vanilla Syrup"],
        "brands": ["Starbucks", "Illy", "Lindt", "Godiva", "Monin"],
        "attributes": {"Weight": ["250g", "500g", "1kg"], "Flavor": ["Classic", "Caramel", "Dark Chocolate"]},
        "price_range": (200, 1500), "parent_cat": "food-beverages"
    },
    "beauty-health": {
        "names": ["Vitamin C Serum", "Hydrating Moisturizer", "Sunscreen SPF 50", "Night Cream"],
        "brands": ["CeraVe", "La Roche-Posay", "Neutrogena", "Olay", "L'Oreal"],
        "attributes": {"Volume": ["30ml", "50ml", "100ml"], "Skin Type": ["Oily", "Dry", "Combination"]},
        "price_range": (300, 2000), "parent_cat": ""
    }
}

def setup_master_images():
    master_dir = "master_images"
    os.makedirs(master_dir, exist_ok=True)
    local_image_map = {}
    
    print("📥 Downloading 12 real product photos from Unsplash CDN...")
    headers = {'User-Agent': 'Mozilla/5.0'}
    
    for category, urls in IMAGE_SOURCES.items():
        local_image_map[category] = []
        for idx, url in enumerate(urls):
            file_path = os.path.join(master_dir, f"{category}_{idx}.jpg")
            local_image_map[category].append(file_path)
            
            if not os.path.exists(file_path):
                try:
                    response = requests.get(url, headers=headers, timeout=10)
                    if response.status_code == 200:
                        with open(file_path, 'wb') as f:
                            f.write(response.content)
                except Exception as e:
                    print(f"⚠️ Failed to download {category} image: {e}")
    
    print("✅ Master images ready!\n")
    return local_image_map

def generate_random_sku(brand):
    chars = ''.join(random.choices(string.digits, k=4))
    return f"SKU-{brand[:3].upper()}-{chars}"

def generate_data(zip_index, images_folder, local_image_map):
    products, variants, attributes, offers = [], [], [], []
    categories_keys = list(MOCK_DB.keys())
    
    for i in range(1, PRODUCTS_PER_ZIP + 1):
        prod_ref = f"z{zip_index}_p{i}"
        cat_slug = random.choice(categories_keys)
        cat_data = MOCK_DB[cat_slug]
        
        brand = random.choice(cat_data["brands"])
        name = random.choice(cat_data["names"])
        full_title = f"{brand} {name}"
        
        base_price = round(random.uniform(cat_data["price_range"][0], cat_data["price_range"][1]) / 100) * 100
        
        # FIX: Changed "" to None
        compare_price = int(base_price * random.uniform(1.1, 1.3)) if random.choice([True, False]) else None
        
        prod_sku = generate_random_sku(brand)
        cats = f"{cat_slug},{cat_data['parent_cat']}" if cat_data['parent_cat'] else cat_slug
        
        image_filename = f"{prod_sku.lower()}.jpg"
        target_image_path = os.path.join(images_folder, image_filename)
        source_image_path = random.choice(local_image_map[cat_slug])
        
        if os.path.exists(source_image_path):
            shutil.copy(source_image_path, target_image_path)
        else:
            valid_png_base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="
            with open(target_image_path, "wb") as f:
                f.write(base64.b64decode(valid_png_base64))
        
        products.append({
            "reference_id": prod_ref,
            "title": full_title,
            "short_description": f"Premium {cat_slug.replace('-', ' ')} item.",
            "description": f"Experience the highest quality with the {full_title}.",
            "base_price": base_price,
            "compare_at_price": compare_price,  # Now inserting None (NULL) instead of ""
            "currency": "EGP",
            "sku": prod_sku,
            "categories": cats,
            "image": image_filename 
        })
        
        attr_keys = list(cat_data["attributes"].keys())
        chosen_attr_keys = random.sample(attr_keys, random.choice([1, 2]))
        num_variants = random.randint(1, 3)
        variant_refs = []
        
        for v_idx in range(1, num_variants + 1):
            var_ref = f"z{zip_index}_v{i}_{v_idx}"
            variant_refs.append(var_ref)
            
            var_attrs = {}
            for key in chosen_attr_keys:
                var_attrs[key] = random.choice(cat_data["attributes"][key])
            
            var_title_parts = list(var_attrs.values())
            var_title = " / ".join(var_title_parts)
            option_summary = ", ".join([f"{k}: {v}" for k, v in var_attrs.items()])
            var_price = base_price + (v_idx * 100 if "Storage" in chosen_attr_keys else 0)
            
            variants.append({
                "reference_id": var_ref, 
                "product_reference_id": prod_ref, 
                "title": var_title,
                "option_summary": option_summary, 
                "sku": f"{prod_sku}-{v_idx}",
                "barcode": f"{random.randint(1000000000, 9999999999)}", 
                "price": var_price,
                "compare_at_price": compare_price if compare_price else None, # FIX: Changed "" to None
                "stock_qty": random.randint(0, 200), 
                "is_default": 1 if v_idx == 1 else 0
            })
            
            for k, v in var_attrs.items():
                attributes.append({
                    "variant_reference_id": var_ref, "attribute_name": k, "attribute_value": v
                })
        
        offer_types = ["percentage", "fixed", "buy_x_get_y"]
        o_type = random.choice(offer_types)
        target_vars = ",".join(variant_refs) 
        
        # FIX: Changed all "" to None in the offers dictionary
        offers.append({
            "product_reference_id": prod_ref, 
            "type": o_type,
            "label": f"Special {o_type.replace('_', ' ').title()} Deal",
            "percentage_value": random.choice([10, 15, 20]) if o_type == "percentage" else None,
            "fixed_amount": round(base_price * 0.1) if o_type == "fixed" else None,
            "buy_qty": random.choice([1, 2]) if o_type == "buy_x_get_y" else None,
            "get_qty": 1 if o_type == "buy_x_get_y" else None,
            "starts_at": "2024-01-01 00:00:00", 
            "ends_at": "2024-12-31 23:59:59",
            "target_buy_variants": target_vars,
            "target_reward_variants": target_vars if o_type == "buy_x_get_y" else None
        })

    return pd.DataFrame(products), pd.DataFrame(variants), pd.DataFrame(attributes), pd.DataFrame(offers)

# --- Execution ---
output_dir = "realistic_ecommerce_imports"
os.makedirs(output_dir, exist_ok=True)

local_image_map = setup_master_images()

print(f"🚀 Building {TOTAL_ZIPS} ZIP files...")

for z in range(1, TOTAL_ZIPS + 1):
    temp_folder = os.path.join(output_dir, f"temp_zip_{z}")
    images_folder = os.path.join(temp_folder, "images")
    os.makedirs(images_folder, exist_ok=True)
    
    df_p, df_v, df_a, df_o = generate_data(z, images_folder, local_image_map)
    
    excel_path = os.path.join(temp_folder, "data.xlsx")
    with pd.ExcelWriter(excel_path, engine='openpyxl') as writer:
        df_p.to_excel(writer, sheet_name='Products', index=False)
        df_v.to_excel(writer, sheet_name='Variants', index=False)
        df_a.to_excel(writer, sheet_name='Attributes', index=False)
        df_o.to_excel(writer, sheet_name='Offers', index=False)
        
    zip_filename = os.path.join(output_dir, f"ecommerce_import_batch_{z}.zip")
    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(temp_folder):
            for file in files:
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, temp_folder)
                zipf.write(file_path, arcname)
                
    shutil.rmtree(temp_folder)

print(f"🎉 Done! Check the '{output_dir}' directory.")