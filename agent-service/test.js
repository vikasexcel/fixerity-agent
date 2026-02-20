import { backfillJobEmbeddings } from "./src/services/jobEmbeddingService.js";
import { backfillSellerEmbeddings } from "./src/services/sellerEmbeddingService.js";

await backfillJobEmbeddings();     // from job_embedding_service.js
await backfillSellerEmbeddings();  // from seller_embedding_service.js