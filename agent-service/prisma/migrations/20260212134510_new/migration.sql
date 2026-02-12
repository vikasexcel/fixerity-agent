-- CreateTable
CREATE TABLE "job_listings" (
    "job_id" TEXT NOT NULL,
    "buyer_id" TEXT NOT NULL,
    "service_category_id" INTEGER NOT NULL,
    "title" TEXT NOT NULL,
    "description" TEXT,
    "budget" JSONB NOT NULL,
    "start_date" TEXT,
    "end_date" TEXT,
    "location" JSONB,
    "priorities" JSONB,
    "specific_requirements" JSONB,
    "status" TEXT NOT NULL DEFAULT 'open',
    "num_bids_received" INTEGER NOT NULL DEFAULT 0,
    "selected_seller_id" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "job_listings_pkey" PRIMARY KEY ("job_id")
);

-- CreateTable
CREATE TABLE "seller_profiles" (
    "seller_id" TEXT NOT NULL,
    "service_categories" INTEGER[],
    "service_area" JSONB,
    "availability" JSONB,
    "credentials" JSONB,
    "pricing" JSONB,
    "preferences" JSONB,
    "bio" TEXT,
    "profile_completeness_score" INTEGER NOT NULL DEFAULT 0,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "total_bids_submitted" INTEGER NOT NULL DEFAULT 0,
    "total_bids_accepted" INTEGER NOT NULL DEFAULT 0,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "seller_profiles_pkey" PRIMARY KEY ("seller_id")
);

-- CreateTable
CREATE TABLE "seller_bids" (
    "bid_id" TEXT NOT NULL,
    "job_id" TEXT NOT NULL,
    "seller_id" TEXT NOT NULL,
    "quoted_price" DECIMAL(65,30) NOT NULL,
    "quoted_timeline" TEXT,
    "quoted_completion_days" INTEGER,
    "payment_terms" TEXT,
    "can_meet_dates" BOOLEAN,
    "message" TEXT,
    "seller_credentials" JSONB,
    "status" TEXT NOT NULL DEFAULT 'pending',
    "buyer_response" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "seller_bids_pkey" PRIMARY KEY ("bid_id")
);

-- CreateTable
CREATE TABLE "conversation_sessions" (
    "session_id" TEXT NOT NULL,
    "user_id" TEXT NOT NULL,
    "user_type" TEXT NOT NULL,
    "access_token" TEXT NOT NULL,
    "phase" TEXT NOT NULL DEFAULT 'conversation',
    "state" JSONB NOT NULL,
    "job_id" TEXT,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "conversation_sessions_pkey" PRIMARY KEY ("session_id")
);

-- CreateTable
CREATE TABLE "conversation_messages" (
    "id" SERIAL NOT NULL,
    "session_id" TEXT NOT NULL,
    "role" TEXT NOT NULL,
    "content" TEXT NOT NULL,
    "metadata" JSONB,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "conversation_messages_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "negotiation_sessions" (
    "id" TEXT NOT NULL,
    "job_id" TEXT NOT NULL,
    "provider_id" TEXT NOT NULL,
    "buyer_id" TEXT NOT NULL,
    "state" JSONB NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'collecting',
    "quote" JSONB,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "negotiation_sessions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "negotiation_messages" (
    "id" SERIAL NOT NULL,
    "negotiation_id" TEXT NOT NULL,
    "role" TEXT NOT NULL,
    "message" TEXT NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "negotiation_messages_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_memories" (
    "id" TEXT NOT NULL,
    "user_id" TEXT NOT NULL,
    "user_type" TEXT NOT NULL,
    "memory_type" TEXT NOT NULL,
    "category" TEXT,
    "content" TEXT NOT NULL,
    "metadata" JSONB NOT NULL,
    "relevance_score" DOUBLE PRECISION NOT NULL DEFAULT 1.0,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "user_memories_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "cache_entries" (
    "id" TEXT NOT NULL,
    "key" TEXT NOT NULL,
    "value" JSONB NOT NULL,
    "expires_at" TIMESTAMP(3) NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "cache_entries_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "conversation_sessions_user_id_user_type_idx" ON "conversation_sessions"("user_id", "user_type");

-- CreateIndex
CREATE INDEX "conversation_sessions_user_id_is_active_idx" ON "conversation_sessions"("user_id", "is_active");

-- CreateIndex
CREATE INDEX "conversation_messages_session_id_created_at_idx" ON "conversation_messages"("session_id", "created_at");

-- CreateIndex
CREATE INDEX "negotiation_sessions_job_id_idx" ON "negotiation_sessions"("job_id");

-- CreateIndex
CREATE INDEX "negotiation_sessions_provider_id_idx" ON "negotiation_sessions"("provider_id");

-- CreateIndex
CREATE UNIQUE INDEX "negotiation_sessions_job_id_provider_id_key" ON "negotiation_sessions"("job_id", "provider_id");

-- CreateIndex
CREATE INDEX "negotiation_messages_negotiation_id_created_at_idx" ON "negotiation_messages"("negotiation_id", "created_at");

-- CreateIndex
CREATE INDEX "user_memories_user_id_user_type_idx" ON "user_memories"("user_id", "user_type");

-- CreateIndex
CREATE INDEX "user_memories_user_id_category_idx" ON "user_memories"("user_id", "category");

-- CreateIndex
CREATE INDEX "user_memories_memory_type_idx" ON "user_memories"("memory_type");

-- CreateIndex
CREATE UNIQUE INDEX "cache_entries_key_key" ON "cache_entries"("key");

-- CreateIndex
CREATE INDEX "cache_entries_key_idx" ON "cache_entries"("key");

-- CreateIndex
CREATE INDEX "cache_entries_expires_at_idx" ON "cache_entries"("expires_at");

-- AddForeignKey
ALTER TABLE "seller_bids" ADD CONSTRAINT "seller_bids_job_id_fkey" FOREIGN KEY ("job_id") REFERENCES "job_listings"("job_id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "seller_bids" ADD CONSTRAINT "seller_bids_seller_id_fkey" FOREIGN KEY ("seller_id") REFERENCES "seller_profiles"("seller_id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "conversation_messages" ADD CONSTRAINT "conversation_messages_session_id_fkey" FOREIGN KEY ("session_id") REFERENCES "conversation_sessions"("session_id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "negotiation_messages" ADD CONSTRAINT "negotiation_messages_negotiation_id_fkey" FOREIGN KEY ("negotiation_id") REFERENCES "negotiation_sessions"("id") ON DELETE CASCADE ON UPDATE CASCADE;
