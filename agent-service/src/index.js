import "dotenv/config";
import express from "express";
import cors from "cors";
import buyerAgentV2Router from "./routes/buyerAgentV2.js";
import sellerAgentV2Router from "./routes/sellerAgentV2.js";

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

app.listen(PORT, () => {
  console.log(`Fixerity Agents API running on port ${PORT}`);
});
