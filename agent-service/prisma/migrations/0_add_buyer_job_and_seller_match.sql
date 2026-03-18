-- CreateTable
CREATE TABLE IF NOT EXISTS "buyer_jobs" (
    "id" TEXT NOT NULL,
    "thread_id" TEXT NOT NULL,
    "job_title" TEXT NOT NULL,
    "job_description" TEXT NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'published',
    "pinecone_job_id" TEXT,
    "category_search_query" TEXT,
    "category_id" TEXT,
    "category_name" TEXT,
    "subcategory_id" TEXT,
    "subcategory_name" TEXT,
    "service_id" TEXT,
    "service_name" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "buyer_jobs_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE IF NOT EXISTS "seller_matches" (
    "id" TEXT NOT NULL,
    "buyer_job_id" TEXT NOT NULL,
    "profile_id" TEXT NOT NULL,
    "vector_rank" INTEGER NOT NULL,
    "llm_score" INTEGER NOT NULL,
    "match_explanation" TEXT NOT NULL,
    "final_rank" INTEGER NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "seller_matches_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX IF NOT EXISTS "seller_matches_buyer_job_id_final_rank_key" ON "seller_matches"("buyer_job_id", "final_rank");

-- AddForeignKey
ALTER TABLE "seller_matches" ADD CONSTRAINT "seller_matches_buyer_job_id_fkey" FOREIGN KEY ("buyer_job_id") REFERENCES "buyer_jobs"("id") ON DELETE CASCADE ON UPDATE CASCADE;
