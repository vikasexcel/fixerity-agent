import "dotenv/config";
import express from "express";
import cors from "cors";
import buyerAgentV2Router from "./routes/buyerAgentV2.js";
import sellerAgentV2Router from "./routes/sellerAgentV2.js";
import { prisma } from "./db/prisma.js";

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

// Health check
app.get("/health", (req, res) => {
  res.json({ status: "ok", service: "buyer-agentv2" });
});

// Buyer Agent V2 routes
app.use("/buyer-agentv2", buyerAgentV2Router);

// Seller Agent V2 routes
app.use("/seller-agentv2", sellerAgentV2Router);

prisma
  .$connect()
  .then(() => {
    console.log("Connected to Postgres database");
    app.listen(PORT, () => {
      console.log(`Fixerity Agents API running on port ${PORT}`);
    });
  })
  .catch((err) => {
    console.error("Failed to connect to Postgres database:", err);
    process.exitCode = 1;
  });
