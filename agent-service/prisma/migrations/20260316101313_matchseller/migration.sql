-- AlterTable
ALTER TABLE "BuyerThreadState" ADD COLUMN     "matchedSellers" JSONB,
ADD COLUMN     "matchingStatus" TEXT;
