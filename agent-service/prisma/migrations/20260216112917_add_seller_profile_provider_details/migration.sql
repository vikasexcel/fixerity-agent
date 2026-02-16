-- Enable pgvector extension (required for seller_embeddings)
CREATE EXTENSION IF NOT EXISTS vector;

-- AlterTable: Add provider details columns. provider_id nullable initially for existing rows.
ALTER TABLE "seller_profiles" ADD COLUMN     "contact_number" TEXT,
ADD COLUMN     "email" TEXT,
ADD COLUMN     "first_name" TEXT,
ADD COLUMN     "gender" INTEGER,
ADD COLUMN     "last_name" TEXT,
ADD COLUMN     "provider_id" INTEGER;

-- Backfill provider_id: if seller_id is numeric, use it; else use 0 as placeholder for existing rows
UPDATE "seller_profiles" SET "provider_id" = CASE
  WHEN "seller_id" ~ '^[0-9]+$' THEN "seller_id"::INTEGER
  ELSE 0
END
WHERE "provider_id" IS NULL;

-- Make provider_id NOT NULL for new rows (existing rows now have value)
ALTER TABLE "seller_profiles" ALTER COLUMN "provider_id" SET NOT NULL;

-- CreateTable
CREATE TABLE "seller_embeddings" (
    "id" TEXT NOT NULL,
    "seller_id" TEXT NOT NULL,
    "embedding" vector(1536) NOT NULL,
    "searchable_text" TEXT NOT NULL,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "seller_embeddings_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "seller_embeddings_seller_id_idx" ON "seller_embeddings"("seller_id");

-- CreateIndex
CREATE INDEX "seller_profiles_provider_id_idx" ON "seller_profiles"("provider_id");

-- AddForeignKey
ALTER TABLE "seller_embeddings" ADD CONSTRAINT "seller_embeddings_seller_id_fkey" FOREIGN KEY ("seller_id") REFERENCES "seller_profiles"("seller_id") ON DELETE CASCADE ON UPDATE CASCADE;
