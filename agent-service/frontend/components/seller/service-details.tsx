'use client';

import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { type ProviderServiceInfo } from '@/lib/provider-api';

interface ServiceDetailsProps {
  service: ProviderServiceInfo;
  onClose: () => void;
}

export function ServiceDetails({ service, onClose }: ServiceDetailsProps) {
  return (
    <>
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div className="bg-card border border-border rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-border">
            <h2 className="text-2xl font-bold text-foreground">Service Details</h2>
            <button
              onClick={onClose}
              className="text-muted-foreground hover:text-foreground transition-colors"
            >
              <X size={24} />
            </button>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto p-6 space-y-6">
            <div>
              <h3 className="text-lg font-semibold text-foreground mb-2">
                {service.service_cat_name}
              </h3>
              <p className="text-sm text-muted-foreground">
                Service Category ID: {service.service_cat_id}
              </p>
              <p className="text-sm text-muted-foreground">
                Provider Service ID: {service.provider_service_id}
              </p>
              {(service.min_price !== null && service.min_price !== undefined) || 
               (service.max_price !== null && service.max_price !== undefined) ? (
                <p className="text-sm text-foreground mt-2">
                  <span className="font-medium">Price Range: </span>
                  {service.min_price !== null && service.min_price !== undefined ? `$${service.min_price}` : ''}
                  {service.min_price !== null && service.min_price !== undefined && 
                   service.max_price !== null && service.max_price !== undefined ? ' - ' : ''}
                  {service.max_price !== null && service.max_price !== undefined ? `$${service.max_price}` : ''}
                </p>
              ) : null}
              {service.deadline_in_days !== null && service.deadline_in_days !== undefined ? (
                <p className="text-sm text-foreground mt-2">
                  <span className="font-medium">Deadline: </span>
                  {service.deadline_in_days} day{service.deadline_in_days !== 1 ? 's' : ''}
                </p>
              ) : null}
            </div>

            <div>
              <h4 className="text-sm font-medium text-foreground mb-2">Status</h4>
              <div className="flex gap-2">
                <span
                  className={`text-xs px-2 py-1 rounded ${
                    service.status === 1
                      ? 'bg-green-500/20 text-green-600 dark:text-green-400'
                      : 'bg-gray-500/20 text-gray-600 dark:text-gray-400'
                  }`}
                >
                  {service.status === 1 ? 'Active' : 'Inactive'}
                </span>
                <span
                  className={`text-xs px-2 py-1 rounded ${
                    service.current_status === 1
                      ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400'
                      : 'bg-gray-500/20 text-gray-600 dark:text-gray-400'
                  }`}
                >
                  {service.current_status === 1 ? 'Available' : 'Unavailable'}
                </span>
              </div>
            </div>

            {service.subcategories && service.subcategories.length > 0 && (
              <div>
                <h4 className="text-sm font-medium text-foreground mb-2">Subcategories</h4>
                <div className="flex flex-wrap gap-2">
                  {service.subcategories.map((sub) => (
                    <span
                      key={sub.category_id}
                      className="bg-secondary text-secondary-foreground text-xs px-3 py-1 rounded"
                    >
                      {sub.category_name} (#{sub.category_id})
                    </span>
                  ))}
                </div>
              </div>
            )}

            {service.packages && service.packages.length > 0 && (
              <div>
                <h4 className="text-sm font-medium text-foreground mb-2">Packages</h4>
                <div className="space-y-2">
                  {service.packages.map((pkg) => (
                    <div
                      key={pkg.package_id}
                      className="border border-border rounded-lg p-3 bg-background"
                    >
                      <div className="flex items-start justify-between">
                        <div>
                          <p className="font-medium text-foreground">{pkg.package_name}</p>
                          {pkg.package_description && (
                            <p className="text-sm text-muted-foreground mt-1">
                              {pkg.package_description}
                            </p>
                          )}
                        </div>
                        <div className="text-right">
                          <p className="font-semibold text-foreground">
                            ${pkg.package_price}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Max: {pkg.max_book_quantity}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {(!service.subcategories || service.subcategories.length === 0) &&
              (!service.packages || service.packages.length === 0) && (
                <div className="text-center py-8 text-muted-foreground">
                  <p>No subcategories or packages configured yet.</p>
                </div>
              )}
          </div>

          {/* Footer */}
          <div className="border-t border-border p-6">
            <Button onClick={onClose} variant="outline">
              Close
            </Button>
          </div>
        </div>
      </div>
    </>
  );
}
