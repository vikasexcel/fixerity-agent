import { DataTypes } from 'sequelize';
import { sequelize } from '../db.js';

const JobListing = sequelize.define('JobListing', {
    job_id: {
      type: DataTypes.STRING,
      primaryKey: true,
      allowNull: false,
    },
    buyer_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    service_category_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    title: {
      type: DataTypes.STRING(255),
      allowNull: false,
    },
    description: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
    budget: {
      type: DataTypes.JSON,
      allowNull: false,
      comment: '{ min: 100, max: 200 }'
    },
    start_date: {
      type: DataTypes.STRING,
      allowNull: true,
      comment: 'YYYY-MM-DD or "ASAP"'
    },
    end_date: {
      type: DataTypes.STRING,
      allowNull: true,
      comment: 'YYYY-MM-DD or "flexible"'
    },
    location: {
      type: DataTypes.JSON,
      allowNull: true,
      comment: '{ address: "...", lat: 0.0, lng: 0.0 }'
    },
    priorities: {
      type: DataTypes.JSON,
      allowNull: true,
      comment: 'Job priorities and requirements'
    },
    specific_requirements: {
      type: DataTypes.JSON,
      allowNull: true,
      comment: 'Service-specific fields (e.g., roofing details)'
    },
    status: {
      type: DataTypes.ENUM('open', 'in_progress', 'completed', 'cancelled'),
      defaultValue: 'open',
    },
    selected_seller_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
      comment: 'Seller who won the job'
    },
    num_bids_received: {
      type: DataTypes.INTEGER,
      defaultValue: 0,
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
    tableName: 'job_listings',
    timestamps: true,
    underscored: true,
    indexes: [
      { fields: ['buyer_id'] },
      { fields: ['service_category_id'] },
      { fields: ['status'] },
      { fields: ['created_at'] },
    ]
  });

  JobListing.associate = (models) => {
    // JobListing.hasMany(models.SellerBid, { foreignKey: 'job_id' });
  };

export { JobListing };
export default JobListing;