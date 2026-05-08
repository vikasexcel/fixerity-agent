-- CreateTable
CREATE TABLE "seller_profiles" (
    "id" TEXT NOT NULL,
    "thread_id" TEXT NOT NULL,
    "profile_description" TEXT NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'published',
    "pinecone_profile_id" TEXT,
    "category_search_query" TEXT,
    "category_id" TEXT,
    "category_name" TEXT,
    "subcategory_id" TEXT,
    "subcategory_name" TEXT,
    "service_id" TEXT,
    "service_name" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "seller_profiles_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "job_matches" (
    "id" TEXT NOT NULL,
    "seller_profile_id" TEXT NOT NULL,
    "buyer_job_id" TEXT NOT NULL,
    "vector_rank" INTEGER NOT NULL,
    "llm_score" INTEGER NOT NULL,
    "match_explanation" TEXT NOT NULL,
    "final_rank" INTEGER NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "job_matches_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "job_matches_seller_profile_id_final_rank_key" ON "job_matches"("seller_profile_id", "final_rank");

-- AddForeignKey
ALTER TABLE "job_matches" ADD CONSTRAINT "job_matches_seller_profile_id_fkey" FOREIGN KEY ("seller_profile_id") REFERENCES "seller_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;
