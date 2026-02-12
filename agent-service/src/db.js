import dotenv from 'dotenv';
dotenv.config();
import { Sequelize } from "sequelize";
export const sequelize = new Sequelize(
  process.env.DB_DATABASE,
  process.env.DB_USERNAME,
  process.env.DB_PASSWORD,
  {
    host: "localhost",
    dialect: "mysql",
    port:3309,
    logging: false, // turn off SQL logs
  }
);
