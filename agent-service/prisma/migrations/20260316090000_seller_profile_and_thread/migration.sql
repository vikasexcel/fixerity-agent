-- CreateTable
CREATE TABLE "SellerProfile" (
    "id" TEXT NOT NULL,
    "threadId" TEXT NOT NULL,
    "sellerProfile" TEXT NOT NULL,
    "profileMetadata" JSONB,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "SellerProfile_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "SellerThreadState" (
    "id" TEXT NOT NULL,
    "threadId" TEXT NOT NULL,
    "status" TEXT NOT NULL,
    "messages" JSONB NOT NULL,
    "questionCount" INTEGER NOT NULL,
    "domainQuestionCount" INTEGER NOT NULL,
    "domainPhaseComplete" BOOLEAN NOT NULL,
    "profileAnswers" JSONB NOT NULL,
    "sellerProfile" TEXT,
    "placeholders" JSONB NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "SellerThreadState_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "SellerProfile_threadId_idx" ON "SellerProfile"("threadId");

-- CreateIndex
CREATE UNIQUE INDEX "SellerThreadState_threadId_key" ON "SellerThreadState"("threadId");

