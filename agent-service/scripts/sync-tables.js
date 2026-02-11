import dotenv from 'dotenv';
dotenv.config();

import { sequelize } from '../src/db.js';
import { JobListing } from '../src/models/JobListing.js';
import { SellerProfile } from '../src/models/SellerProfile.js';
import { SellerBid } from '../src/models/SellerBid.js';

async function main() {
  try {
    await sequelize.authenticate();
    console.log('Database connected.');

    await JobListing.sync();
    console.log('Table job_listings ready.');
    await SellerProfile.sync();
    console.log('Table seller_profiles ready.');
    await SellerBid.sync();
    console.log('Table seller_bids ready.');

    console.log('Done.');
    process.exit(0);
  } catch (err) {
    console.error('Error:', err.message);
    process.exit(1);
  }
}

main();