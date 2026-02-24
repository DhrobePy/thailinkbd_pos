-- Thai Link BD Inventory Management System Seed Data
-- Initial data for testing and setup

USE `ujjalfmc_thai_link_inventory`;

-- Insert default admin user (password: admin123)

-- Insert product categories
INSERT INTO `categories` (`name`, `description`, `parent_id`) VALUES
('Skincare', 'Skincare products including cleansers, moisturizers, serums', NULL),
('Makeup', 'Cosmetic products for face and body enhancement', NULL),
('Haircare', 'Hair treatment and styling products', NULL),
('Fragrance', 'Perfumes and body sprays', NULL),
('Body Care', 'Body lotions, scrubs, and treatments', NULL);

-- Insert subcategories
INSERT INTO `categories` (`name`, `description`, `parent_id`) VALUES
('Cleansers', 'Face and body cleansing products', 1),
('Moisturizers', 'Face and body moisturizing products', 1),
('Serums', 'Treatment serums and essences', 1),
('Foundation', 'Base makeup products', 2),
('Lipstick', 'Lip color products', 2),
('Eyeshadow', 'Eye makeup products', 2),
('Shampoo', 'Hair cleansing products', 3),
('Conditioner', 'Hair conditioning products', 3),
('Hair Oil', 'Hair treatment oils', 3),
('Perfume', 'Eau de parfum and eau de toilette', 4),
('Body Spray', 'Light fragrance sprays', 4),
('Body Lotion', 'Body moisturizing products', 5),
('Body Scrub', 'Exfoliating body products', 5);

-- Insert brands
INSERT INTO `brands` (`name`, `description`) VALUES
('L\'Oreal', 'International beauty and cosmetics brand'),
('Maybelline', 'Popular makeup and cosmetics brand'),
('Garnier', 'Natural beauty and skincare products'),
('Revlon', 'Professional makeup and beauty products'),
('Nivea', 'Skincare and body care products'),
('Pantene', 'Hair care and styling products'),
('Head & Shoulders', 'Anti-dandruff hair care products'),
('Dove', 'Personal care and beauty products'),
('Olay', 'Anti-aging and skincare products'),
('CoverGirl', 'Makeup and cosmetics brand');

-- Insert suppliers
INSERT INTO `suppliers` (`name`, `contact_person`, `email`, `phone`, `address`, `city`, `country`) VALUES
('Beauty Distributors BD', 'Mr. Rahman', 'info@beautybd.com', '+8801711111111', 'House 123, Road 15, Dhanmondi', 'Dhaka', 'Bangladesh'),
('Cosmetics Wholesale Ltd', 'Ms. Fatima', 'sales@cosmeticswholesale.com', '+8801722222222', 'Plot 45, Tejgaon Industrial Area', 'Dhaka', 'Bangladesh'),
('International Beauty Supply', 'Mr. Ahmed', 'contact@ibsupply.com', '+8801733333333', 'Sector 7, Uttara', 'Dhaka', 'Bangladesh');

-- Insert sample products
INSERT INTO `products` (`name`, `description`, `sku`, `category_id`, `brand_id`, `supplier_id`, `cost_price`, `selling_price`, `wholesale_price`, `min_stock_level`, `reorder_point`, `has_variants`) VALUES
('L\'Oreal Paris Revitalift Anti-Aging Cream', 'Advanced anti-aging moisturizer with Pro-Retinol', 'LOR-REV-001', 2, 1, 1, 850.00, 1200.00, 1000.00, 10, 5, 1),
('Maybelline Fit Me Foundation', 'Lightweight liquid foundation for all skin types', 'MAY-FIT-001', 9, 2, 1, 650.00, 950.00, 800.00, 15, 8, 1),
('Garnier Micellar Water', 'Gentle makeup remover and cleanser', 'GAR-MIC-001', 6, 3, 2, 320.00, 480.00, 400.00, 20, 10, 0),
('Pantene Pro-V Shampoo', 'Strengthening shampoo for damaged hair', 'PAN-PRO-001', 12, 6, 2, 280.00, 420.00, 350.00, 25, 12, 1),
('Nivea Soft Moisturizing Cream', 'Light moisturizing cream for face and body', 'NIV-SOF-001', 17, 5, 3, 180.00, 280.00, 230.00, 30, 15, 0),
('Revlon ColorStay Lipstick', 'Long-lasting matte lipstick', 'REV-COL-001', 10, 4, 1, 450.00, 680.00, 570.00, 12, 6, 1),
('Dove Beauty Bar Soap', 'Moisturizing beauty bar with 1/4 moisturizing cream', 'DOV-BEA-001', 6, 8, 3, 85.00, 130.00, 110.00, 50, 25, 1),
('Olay Regenerist Serum', 'Anti-aging serum with amino-peptides', 'OLA-REG-001', 8, 9, 2, 1200.00, 1800.00, 1500.00, 8, 4, 0);

-- Insert product variants
INSERT INTO `product_variants` (`product_id`, `variant_name`, `sku`, `size`, `color`, `cost_price`, `selling_price`, `wholesale_price`) VALUES
(1, 'L\'Oreal Revitalift 50ml', 'LOR-REV-001-50', '50ml', NULL, 850.00, 1200.00, 1000.00),
(1, 'L\'Oreal Revitalift 75ml', 'LOR-REV-001-75', '75ml', NULL, 1200.00, 1700.00, 1400.00),
(2, 'Fit Me Foundation Ivory', 'MAY-FIT-001-IVY', '30ml', 'Ivory', 650.00, 950.00, 800.00),
(2, 'Fit Me Foundation Beige', 'MAY-FIT-001-BEI', '30ml', 'Beige', 650.00, 950.00, 800.00),
(2, 'Fit Me Foundation Caramel', 'MAY-FIT-001-CAR', '30ml', 'Caramel', 650.00, 950.00, 800.00),
(4, 'Pantene Shampoo 200ml', 'PAN-PRO-001-200', '200ml', NULL, 280.00, 420.00, 350.00),
(4, 'Pantene Shampoo 400ml', 'PAN-PRO-001-400', '400ml', NULL, 480.00, 720.00, 600.00),
(6, 'ColorStay Red', 'REV-COL-001-RED', NULL, 'Red', 450.00, 680.00, 570.00),
(6, 'ColorStay Pink', 'REV-COL-001-PNK', NULL, 'Pink', 450.00, 680.00, 570.00),
(6, 'ColorStay Nude', 'REV-COL-001-NUD', NULL, 'Nude', 450.00, 680.00, 570.00),
(7, 'Dove Bar White', 'DOV-BEA-001-WHT', '100g', 'White', 85.00, 130.00, 110.00),
(7, 'Dove Bar Pink', 'DOV-BEA-001-PNK', '100g', 'Pink', 85.00, 130.00, 110.00);

-- Insert initial inventory
INSERT INTO `inventory` (`product_id`, `variant_id`, `quantity`, `location`) VALUES
(1, 1, 25, 'Main Store'),
(1, 2, 15, 'Main Store'),
(2, 3, 30, 'Main Store'),
(2, 4, 25, 'Main Store'),
(2, 5, 20, 'Main Store'),
(3, NULL, 45, 'Main Store'),
(4, 6, 35, 'Main Store'),
(4, 7, 20, 'Main Store'),
(5, NULL, 60, 'Main Store'),
(6, 8, 18, 'Main Store'),
(6, 9, 22, 'Main Store'),
(6, 10, 15, 'Main Store'),
(7, 11, 80, 'Main Store'),
(7, 12, 70, 'Main Store'),
(8, NULL, 12, 'Main Store');

-- Insert sample customers
INSERT INTO `customers` (`name`, `email`, `phone`, `address`, `customer_type`, `discount_percentage`) VALUES
('Walk-in Customer', NULL, NULL, NULL, 'retail', 0.00),
('Beauty Salon XYZ', 'info@beautysalonxyz.com', '+8801811111111', 'House 456, Gulshan-2, Dhaka', 'wholesale', 15.00),
('Cosmetics Corner', 'sales@cosmeticscorner.com', '+8801822222222', 'Shop 12, New Market, Dhaka', 'wholesale', 12.00),
('Fatima Ahmed', 'fatima.ahmed@email.com', '+8801833333333', 'Apartment 5B, Dhanmondi-15, Dhaka', 'retail', 5.00),
('Retail Store ABC', 'contact@retailabc.com', '+8801844444444', 'Plot 78, Mirpur-10, Dhaka', 'wholesale', 18.00);

-- Insert system settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'Thai Link BD', 'string', 'Company name for invoices and receipts'),
('company_address', 'House 123, Road 15, Dhanmondi, Dhaka-1205, Bangladesh', 'string', 'Company address'),
('company_phone', '+880-2-123456789', 'string', 'Company phone number'),
('company_email', 'info@thailinkbd.com', 'string', 'Company email address'),
('tax_rate', '15', 'number', 'Default tax rate percentage'),
('currency', 'BDT', 'string', 'Default currency'),
('low_stock_threshold', '10', 'number', 'Low stock alert threshold'),
('receipt_footer', 'Thank you for shopping with Thai Link BD!', 'string', 'Footer text for receipts'),
('invoice_terms', 'Payment due within 30 days of invoice date.', 'string', 'Default invoice terms and conditions'),
('barcode_prefix', 'TLB', 'string', 'Prefix for generated barcodes');

-- Generate some barcodes for products
INSERT INTO `barcodes` (`product_id`, `variant_id`, `barcode`, `barcode_type`) VALUES
(1, 1, '8901030875021', 'EAN13'),
(1, 2, '8901030875038', 'EAN13'),
(2, 3, '8901030875045', 'EAN13'),
(2, 4, '8901030875052', 'EAN13'),
(2, 5, '8901030875069', 'EAN13'),
(3, NULL, '8901030875076', 'EAN13'),
(4, 6, '8901030875083', 'EAN13'),
(4, 7, '8901030875090', 'EAN13'),
(5, NULL, '8901030875106', 'EAN13'),
(6, 8, '8901030875113', 'EAN13'),
(6, 9, '8901030875120', 'EAN13'),
(6, 10, '8901030875137', 'EAN13'),
(7, 11, '8901030875144', 'EAN13'),
(7, 12, '8901030875151', 'EAN13'),
(8, NULL, '8901030875168', 'EAN13');

