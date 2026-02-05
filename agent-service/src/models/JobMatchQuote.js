import { DataTypes } from 'sequelize';
import { sequelize } from '../db.js';

/**
 * Stores negotiation response per job + provider so recommended agents can be
 * shown from DB without calling the agent again.
 * One row per (job_id, user_id, provider_id).
 */
const JobMatchQuote = sequelize.define('JobMatchQuote', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  job_id: {
    type: DataTypes.STRING(64),
    allowNull: false,
  },
  user_id: {
    type: DataTypes.INTEGER.UNSIGNED,
    allowNull: false,
  },
  provider_id: {
    type: DataTypes.STRING(64),
    allowNull: false,
  },
  seller_name: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  match_score: {
    type: DataTypes.INTEGER,
    allowNull: true,
  },
  // AI response message from provider (e.g. "Thank you for your inquiry...")
  negotiation_message: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  quote_price: {
    type: DataTypes.DECIMAL(12, 2),
    allowNull: true,
  },
  quote_days: {
    type: DataTypes.INTEGER,
    allowNull: true,
  },
  payment_schedule: {
    type: DataTypes.STRING(512),
    allowNull: true,
  },
  licensed: {
    type: DataTypes.BOOLEAN,
    allowNull: true,
  },
  references_available: {
    type: DataTypes.BOOLEAN,
    allowNull: true,
  },
}, {
  tableName: 'job_match_quotes',
  timestamps: true,
  updatedAt: 'updated_at',
  createdAt: 'created_at',
  indexes: [
    { unique: true, fields: ['job_id', 'user_id', 'provider_id'], name: 'job_match_quotes_job_user_provider_unique' },
    { fields: ['job_id', 'user_id'] },
    { fields: ['provider_id'] },
  ],
});

export default JobMatchQuote;
