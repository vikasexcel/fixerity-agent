-- CreateTable
CREATE TABLE "Job" (
    "id" TEXT NOT NULL,
    "threadId" TEXT NOT NULL,
    "jobPost" TEXT NOT NULL,
    "jobMetadata" JSONB NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'created',
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "Job_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "BuyerThreadState" (
    "id" TEXT NOT NULL,
    "threadId" TEXT NOT NULL,
    "status" TEXT NOT NULL,
    "messages" JSONB NOT NULL,
    "questionCount" INTEGER NOT NULL,
    "domainQuestionCount" INTEGER NOT NULL,
    "domainPhaseComplete" BOOLEAN NOT NULL,
    "profileAnswers" JSONB NOT NULL,
    "jobPost" TEXT,
    "placeholders" JSONB NOT NULL,
    "sellerDecisions" JSONB NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "BuyerThreadState_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "Job_threadId_idx" ON "Job"("threadId");

-- CreateIndex
CREATE UNIQUE INDEX "BuyerThreadState_threadId_key" ON "BuyerThreadState"("threadId");
