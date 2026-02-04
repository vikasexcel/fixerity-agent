'use client';

import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { createProviderService } from '@/lib/provider-api';
import { fetchServiceCategories, fetchSubCategories, type ServiceCategory } from '@/lib/services-api';

interface CreateServiceModalProps {
  onClose: () => void;
  onServiceCreate?: () => void;
}

export function CreateServiceModal({ onClose, onServiceCreate }: CreateServiceModalProps) {
  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();
  const providerId = user?.role === 'seller' ? Number(user.id) : 0;

  const [services, setServices] = useState<ServiceCategory[]>([]);
  const [subCategories, setSubCategories] = useState<Array<{ id: number; name: string }>>([]);
  const [selectedServiceCategoryId, setSelectedServiceCategoryId] = useState<string>('');
  const [selectedSubCategoryId, setSelectedSubCategoryId] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!user || !token || user.role !== 'seller' || !providerId) return;

    const loadServices = async () => {
      try {
        // For seller/provider, we might need a different endpoint, but using buyer endpoint for now
        const svc = await fetchServiceCategories(providerId, token);
        setServices(svc);
      } catch (err) {
        console.error('Failed to load services:', err);
        setServices([]);
      }
    };

    loadServices();
  }, [user, token, providerId]);

  useEffect(() => {
    if (!user || !token || !selectedServiceCategoryId) {
      setSubCategories([]);
      return;
    }

    const loadSubCategories = async () => {
      try {
        const subs = await fetchSubCategories(providerId, token, Number(selectedServiceCategoryId));
        setSubCategories(subs);
      } catch (err) {
        console.error('Failed to load subcategories:', err);
        setSubCategories([]);
      }
    };

    loadSubCategories();
  }, [user, token, providerId, selectedServiceCategoryId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !token || user.role !== 'seller' || !providerId) return;
    if (!selectedServiceCategoryId) {
      setError('Please select a service category');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await createProviderService({
        provider_id: providerId,
        access_token: token,
        service_category_id: Number(selectedServiceCategoryId),
      });
      onServiceCreate?.();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create service');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card border border-border rounded-lg shadow-lg w-full max-w-md">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-border">
          <h2 className="text-2xl font-bold text-foreground">Create Service</h2>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors"
          >
            <X size={24} />
          </button>
        </div>

        {/* Content */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="p-3 bg-destructive/10 border border-destructive/20 rounded-lg text-destructive text-sm">
              {error}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-foreground mb-2">
              Service Category *
            </label>
            <select
              value={selectedServiceCategoryId}
              onChange={(e) => {
                setSelectedServiceCategoryId(e.target.value);
                setSelectedSubCategoryId('');
              }}
              className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              required
            >
              <option value="">Select service category</option>
              {services.map((s) => (
                <option key={s.service_category_id} value={s.service_category_id}>
                  {s.service_category_name} (Category #{s.service_category_id})
                </option>
              ))}
            </select>
          </div>

          {selectedServiceCategoryId && subCategories.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-foreground mb-2">
                Sub Category
              </label>
              <select
                value={selectedSubCategoryId}
                onChange={(e) => setSelectedSubCategoryId(e.target.value)}
                className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Select subcategory (optional)</option>
                {subCategories.map((sub) => (
                  <option key={sub.id} value={sub.id}>
                    {sub.name} (Sub category #{sub.id})
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="flex gap-3 pt-4">
            <Button type="button" onClick={onClose} variant="outline" className="flex-1">
              Cancel
            </Button>
            <Button type="submit" className="flex-1" disabled={loading}>
              {loading ? 'Creating...' : 'Create Service'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
