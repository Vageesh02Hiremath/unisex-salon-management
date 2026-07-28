-- Extra services for the customer services and booking panels.
-- Safe to run more than once; existing service names are skipped.

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Classic Hair Wash & Blow Dry', 'Shampoo, conditioning, and neat blow dry finish for men', 400.00, 30, 'Hair', 'Male', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Classic Hair Wash & Blow Dry');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Men Hair Spa', 'Nourishing scalp and hair spa treatment for dry or stressed hair', 900.00, 50, 'Hair', 'Male', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Men Hair Spa');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Face Cleanup (Men)', 'Quick cleansing, scrub, steam, and mask for refreshed skin', 650.00, 35, 'Skincare', 'Male', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Face Cleanup (Men)');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Royal Shave', 'Premium shave with hot towel, foam, and after-shave care', 300.00, 30, 'Shave', 'Male', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Royal Shave');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Women Hair Styling', 'Blow dry, curls, or straight styling for everyday and events', 900.00, 45, 'Hair', 'Female', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Women Hair Styling');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Bridal Makeup Trial', 'Trial makeup session with shade matching and style consultation', 1800.00, 60, 'Makeup', 'Female', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Bridal Makeup Trial');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Kids Hair Wash', 'Gentle shampoo and conditioning for kids', 250.00, 20, 'Hair', 'Kids', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Kids Hair Wash');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Kids Party Styling', 'Simple event styling with soft finish for kids', 600.00, 35, 'Hair', 'Kids', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Kids Party Styling');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Keratin Smoothening', 'Frizz control and smoothening treatment for manageable hair', 3500.00, 120, 'Hair', 'Unisex', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Keratin Smoothening');

INSERT INTO services (name, description, price, duration, category, gender_category, status, image)
SELECT 'Scalp Detox Treatment', 'Deep scalp cleansing treatment to remove buildup and refresh roots', 1200.00, 45, 'Hair', 'Unisex', 'active', NULL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Scalp Detox Treatment');
