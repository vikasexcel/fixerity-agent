import { DataTypes } from 'sequelize';
import { sequelize } from '../db.js';

const SellerProfile = sequelize.define('SellerProfile', {
  seller_id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    allowNull: false,
  },
  service_categories: {
    type: DataTypes.JSON,
    allowNull: false,
    defaultValue: [],
    comment: 'Array of service_category_ids [1, 5, 12]'
  },
  service_area: {
    type: DataTypes.JSON,
    allowNull: true,
    comment: '{ location: "address or lat,lng", radius_miles: 10 }'
  },
  availability: {
    type: DataTypes.JSON,
    allowNull: true,
    comment: '{ weekday_evenings: true, weekends: true, schedule: "Mon-Fri 5-9PM, Sat-Sun 8AM-6PM" }'
  },
  credentials: {
    type: DataTypes.JSON,
    allowNull: true,
    comment: '{ licensed: false, insured: false, years_experience: 2, references_available: true, certifications: [] }'
  },
  pricing: {
    type: DataTypes.JSON,
    allowNull: true,
    comment: '{ hourly_rate_min: 20, hourly_rate_max: 40, fixed_prices: { "standard_cleaning": 80 } }'
  },
  preferences: {
    type: DataTypes.JSON,
    allowNull: true,
    comment: '{ min_job_size_hours: 2, max_travel_distance: 15, provides_materials: false, preferred_payment: ["cash", "card"] }'
  },
  bio: {
    type: DataTypes.TEXT,
    allowNull: true,
    comment: 'Seller introduction/bio text'
  },
  profile_completeness_score: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
    comment: 'Score 0-100 based on filled fields'
  },
  average_rating: {
    type: DataTypes.DECIMAL(3, 2),
    defaultValue: 0.00,
    comment: 'Average rating from completed jobs'
  },
  total_jobs_completed: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
  total_bids_submitted: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
  total_bids_accepted: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
  response_rate: {
    type: DataTypes.DECIMAL(5, 2),
    defaultValue: 0.00,
    comment: 'Percentage of jobs responded to'
  },
  active: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
    comment: 'Is profile active for job matching'
  },
  created_at: {
    type: DataTypes.DATE,
    defaultValue: DataTypes.NOW,
  },
  updated_at: {
    type: DataTypes.DATE,
    defaultValue: DataTypes.NOW,
  }
}, {
  tableName: 'seller_profiles',
  timestamps: true,
  underscored: true,
  indexes: [
    { fields: ['seller_id'] },
    { fields: ['active'] },
    { fields: ['average_rating'] },
  ]
});

export { SellerProfile };
export default SellerProfile;
