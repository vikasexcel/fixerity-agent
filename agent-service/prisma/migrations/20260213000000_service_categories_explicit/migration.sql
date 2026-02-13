-- Seller profiles: store service categories as explicit names (no API)
ALTER TABLE "seller_profiles" ADD COLUMN "service_category_names" TEXT[] DEFAULT '{}';
ALTER TABLE "seller_profiles" DROP COLUMN "service_categories";

-- Job listings: add explicit service name, make category ID optional
ALTER TABLE "job_listings" ADD COLUMN "service_category_name" TEXT;
ALTER TABLE "job_listings" ALTER COLUMN "service_category_id" DROP NOT NULL;
