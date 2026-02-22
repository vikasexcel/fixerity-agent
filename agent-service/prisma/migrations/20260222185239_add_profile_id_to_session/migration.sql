-- AlterTable
ALTER TABLE "conversation_sessions" ADD COLUMN     "profile_id" TEXT;

-- CreateIndex
CREATE INDEX "conversation_sessions_profile_id_idx" ON "conversation_sessions"("profile_id");
