'use client';

import { useState, useEffect } from 'react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { getProviderServices, type ProviderServiceInfo } from '@/lib/provider-api';
import { CreateServiceModal } from './create-service-modal';
import { ServiceDetails } from './service-details';

export function ServiceManagement() {
  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();
  const providerId = user?.role === 'seller' ? Number(user.id) : 0;

  const [services, setServices] = useState<ProviderServiceInfo[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedService, setSelectedService] = useState<ProviderServiceInfo | null>(null);

  useEffect(() => {
    if (!user || !token || user.role !== 'seller' || !providerId) return;

    const loadServices = async () => {
      try {
        setLoading(true);
        const svc = await getProviderServices(providerId, token);
        setServices(svc);
      } catch (err) {
        console.error('Failed to load services:', err);
        setServices([]);
      } finally {
        setLoading(false);
      }
    };

    loadServices();
  }, [user, token, providerId]);

  const handleServiceCreate = () => {
    // Reload services after creation
    if (user && token && providerId) {
      getProviderServices(providerId, token)
        .then(setServices)
        .catch(() => setServices([]));
    }
  };

  if (loading) {
    return (
      <div className="bg-card border border-border rounded-lg p-6">
        <p className="text-muted-foreground">Loading services...</p>
      </div>
    );
  }

  return (
    <>
      <div className="bg-card border border-border rounded-lg p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-foreground">My Services</h2>
          <Button
            onClick={() => setShowCreateModal(true)}
            className="bg-primary hover:bg-primary/90 text-primary-foreground"
          >
            <Plus size={16} className="mr-2" />
            Create Service
          </Button>
        </div>

        {services.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-muted-foreground mb-4">No services created yet</p>
            <Button
              onClick={() => setShowCreateModal(true)}
              variant="outline"
            >
              Create Your First Service
            </Button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {services.map((service) => (
              <div
                key={service.provider_service_id}
                className="border border-border rounded-lg p-4 hover:border-primary transition-colors cursor-pointer"
                onClick={() => setSelectedService(service)}
              >
                <h3 className="font-semibold text-foreground mb-2">
                  {service.service_cat_name}
                </h3>
                <p className="text-sm text-muted-foreground mb-2">
                  Category ID: {service.service_cat_id}
                </p>
                <div className="flex items-center gap-2 mt-4">
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
            ))}
          </div>
        )}
      </div>

      {showCreateModal && (
        <CreateServiceModal
          onClose={() => setShowCreateModal(false)}
          onServiceCreate={handleServiceCreate}
        />
      )}

      {selectedService && (
        <ServiceDetails
          service={selectedService}
          onClose={() => setSelectedService(null)}
        />
      )}
    </>
  );
}
