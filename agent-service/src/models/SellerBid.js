import { DataTypes } from 'sequelize';
import { sequelize } from '../db.js';

const SellerBid = sequelize.define('SellerBid', {
    bid_id: {
      type: DataTypes.STRING,
      primaryKey: true,
      allowNull: false,
    },
    job_id: {
      type: DataTypes.STRING,
      allowNull: false,
      references: {
        model: 'job_listings',
        key: 'job_id'
      }
    },
    seller_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'seller_profiles',
        key: 'seller_id'
      }
    },
    quoted_price: {
      type: DataTypes.DECIMAL(10, 2),
      allowNull: false,
    },
    quoted_timeline: {
      type: DataTypes.STRING,
      allowNull: false,
      comment: 'e.g., "3 days" or "2025-02-20"'
    },
    quoted_completion_days: {
      type: DataTypes.INTEGER,
      allowNull: true,
      comment: 'Number of days to complete'
    },
    payment_terms: {
      type: DataTypes.TEXT,
      allowNull: true,
      comment: 'Payment schedule description'
    },
    can_meet_dates: {
      type: DataTypes.BOOLEAN,
      defaultValue: true,
    },
    message: {
      type: DataTypes.TEXT,
      allowNull: true,
      comment: 'Seller message to buyer'
    },
    line_items: {
      type: DataTypes.JSON,
      allowNull: true,
      comment: 'Detailed pricing breakdown [{ item: "...", cost: 100 }]'
    },
    seller_credentials: {
      type: DataTypes.JSON,
      allowNull: true,
      comment: 'Credentials at time of bid { licensed: true, insured: true }'
    },
    status: {
      type: DataTypes.ENUM('pending', 'accepted', 'rejected', 'withdrawn'),
      defaultValue: 'pending',
    },
    viewed_by_buyer: {
      type: DataTypes.BOOLEAN,
      defaultValue: false,
    },
    buyer_response: {
      type: DataTypes.TEXT,
      allowNull: true,
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
    tableName: 'seller_bids',
    timestamps: true,
    underscored: true,
    indexes: [
      { fields: ['job_id'] },
      { fields: ['seller_id'] },
      { fields: ['status'] },
      { fields: ['created_at'] },
    ]
  });

  SellerBid.associate = (models) => {
    // SellerBid.belongsTo(models.JobListing, { foreignKey: 'job_id' });
    // SellerBid.belongsTo(models.SellerProfile, { foreignKey: 'seller_id' });
  };

export { SellerBid };
export default SellerBid;