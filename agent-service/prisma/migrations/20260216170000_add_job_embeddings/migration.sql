-- CreateTable (pgvector extension already enabled from seller_embeddings)
CREATE TABLE "job_embeddings" (
    "embedding_id" TEXT NOT NULL,
    "job_id" TEXT NOT NULL,
    "embedding" vector(1536) NOT NULL,
    "searchable_text" TEXT NOT NULL,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "job_embeddings_pkey" PRIMARY KEY ("embedding_id")
);

-- CreateIndex
CREATE INDEX "job_embeddings_job_id_idx" ON "job_embeddings"("job_id");

-- AddForeignKey
ALTER TABLE "job_embeddings" ADD CONSTRAINT "job_embeddings_job_id_fkey" FOREIGN KEY ("job_id") REFERENCES "job_listings"("job_id") ON DELETE CASCADE ON UPDATE CASCADE;
