'use client';

import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { updateProviderProfile, getProviderHome, type ProviderHomeResponse } from '@/lib/provider-api';
import { getAgentConfig, saveAgentConfig, type AgentConfig } from '@/lib/agent-config';
import { getProviderServices } from '@/lib/provider-api';

interface EditProfileModalProps {
  onClose: () => void;
  onSave?: () => void;
}

export function EditProfileModal({ onClose, onSave }: EditProfileModalProps) {
  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();
  const providerId = user?.role === 'seller' ? Number(user.id) : 0;

  const [activeTab, setActiveTab] = useState<'profile' | 'agent'>('profile');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [providerData, setProviderData] = useState<ProviderHomeResponse | null>(null);
  
  // Profile form data
  const [profileData, setProfileData] = useState({
    full_name: '',
    last_name: '',
    email: '',
    gender: 1,
    contact_number: '',
    address: '',
    lat: '',
    long: '',
    landmark: '',
    service_radius: '',
    select_country_code: '+1',
    profile_image: null as File | null,
  });

  // Agent config data
  const [agentConfig, setAgentConfig] = useState<AgentConfig>({
    average_rating: 0,
    total_completed_order: 0,
    num_of_rating: 0,
    licensed: false,
    package_list: [],
  });

  useEffect(() => {
    if (!user || !token || user.role !== 'seller' || !providerId) return;

    const loadData = async () => {
      try {
        const [homeData, services] = await Promise.all([
          getProviderHome(providerId, token).catch(() => null),
          getProviderServices(providerId, token).catch(() => []),
        ]);

        if (homeData) {
          setProviderData(homeData);
          setProfileData({
            full_name: homeData.provider_name || '',
            last_name: '',
            email: user.email || '',
            gender: 1,
            contact_number: '',
            address: '',
            lat: '',
            long: '',
            landmark: '',
            service_radius: homeData.provider_service_radius || '',
            select_country_code: '+1',
            profile_image: null,
          });
        }

        // Load agent config for first service category if available
        if (services.length > 0) {
          const firstServiceCategoryId = services[0].service_cat_id;
          try {
            const config = await getAgentConfig(providerId, token, firstServiceCategoryId);
            if (config) {
              setAgentConfig(config);
            } else if (homeData) {
              // Initialize with API data
              setAgentConfig({
                average_rating: homeData.average_rating || 0,
                total_completed_order: homeData.total_completed_order || 0,
                num_of_rating: 0,
                licensed: true,
                package_list: [],
              });
            }
          } catch (err) {
            console.error('Failed to load agent config:', err);
            // Initialize with default values
            if (homeData) {
              setAgentConfig({
                average_rating: homeData.average_rating || 0,
                total_completed_order: homeData.total_completed_order || 0,
                num_of_rating: 0,
                licensed: true,
                package_list: [],
              });
            }
          }
        } else if (homeData) {
          // No services, initialize with API data
          setAgentConfig({
            average_rating: homeData.average_rating || 0,
            total_completed_order: homeData.total_completed_order || 0,
            num_of_rating: 0,
            licensed: true,
            package_list: [],
          });
        }
      } catch (err) {
        console.error('Failed to load data:', err);
      }
    };

    loadData();
  }, [user, token, providerId]);

  const handleProfileSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !token || user.role !== 'seller' || !providerId) return;

    setLoading(true);
    setError(null);

    try {
      await updateProviderProfile({
        provider_id: providerId,
        access_token: token,
        full_name: profileData.full_name,
        last_name: profileData.last_name || '',
        email: profileData.email,
        gender: profileData.gender,
        contact_number: profileData.contact_number,
        address: profileData.address,
        lat: Number(profileData.lat) || 0,
        long: Number(profileData.long) || 0,
        landmark: profileData.landmark || '',
        service_radius: Number(profileData.service_radius) || 0,
        select_country_code: profileData.select_country_code,
        profile_image: profileData.profile_image || undefined,
      });
      onSave?.();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update profile');
    } finally {
      setLoading(false);
    }
  };

  const handleAgentConfigSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!providerId || !token) return;

    setLoading(true);
    setError(null);

    try {
      // Get first service category ID
      const services = await getProviderServices(providerId, token);
      if (services.length === 0) {
        throw new Error('No service categories found. Please add a service first.');
      }
      const firstServiceCategoryId = services[0].service_cat_id;
      
      await saveAgentConfig(providerId, token, firstServiceCategoryId, agentConfig);
      onSave?.();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save agent configuration');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card border border-border rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-border">
          <h2 className="text-2xl font-bold text-foreground">Edit Profile</h2>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors"
          >
            <X size={24} />
          </button>
        </div>

        {/* Tabs */}
        <div className="flex border-b border-border">
          <button
            onClick={() => setActiveTab('profile')}
            className={`flex-1 px-6 py-3 text-sm font-medium transition-colors ${
              activeTab === 'profile'
                ? 'text-primary border-b-2 border-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            Basic Profile
          </button>
          <button
            onClick={() => setActiveTab('agent')}
            className={`flex-1 px-6 py-3 text-sm font-medium transition-colors ${
              activeTab === 'agent'
                ? 'text-primary border-b-2 border-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            Agent Configuration
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6">
          {error && (
            <div className="mb-4 p-3 bg-destructive/10 border border-destructive/20 rounded-lg text-destructive text-sm">
              {error}
            </div>
          )}

          {activeTab === 'profile' && (
            <form onSubmit={handleProfileSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">First Name *</label>
                  <Input
                    value={profileData.full_name}
                    onChange={(e) => setProfileData({ ...profileData, full_name: e.target.value })}
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Last Name</label>
                  <Input
                    value={profileData.last_name}
                    onChange={(e) => setProfileData({ ...profileData, last_name: e.target.value })}
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Email *</label>
                <Input
                  type="email"
                  value={profileData.email}
                  onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Gender *</label>
                <div className="flex gap-4">
                  <label className="flex items-center gap-2">
                    <input
                      type="radio"
                      checked={profileData.gender === 1}
                      onChange={() => setProfileData({ ...profileData, gender: 1 })}
                      className="rounded-full"
                    />
                    <span className="text-sm">Male</span>
                  </label>
                  <label className="flex items-center gap-2">
                    <input
                      type="radio"
                      checked={profileData.gender === 2}
                      onChange={() => setProfileData({ ...profileData, gender: 2 })}
                      className="rounded-full"
                    />
                    <span className="text-sm">Female</span>
                  </label>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Contact Number *</label>
                <Input
                  value={profileData.contact_number}
                  onChange={(e) => setProfileData({ ...profileData, contact_number: e.target.value.replace(/\D/g, '') })}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Address *</label>
                <Input
                  value={profileData.address}
                  onChange={(e) => setProfileData({ ...profileData, address: e.target.value })}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Latitude</label>
                  <Input
                    type="number"
                    step="any"
                    value={profileData.lat}
                    onChange={(e) => setProfileData({ ...profileData, lat: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Longitude</label>
                  <Input
                    type="number"
                    step="any"
                    value={profileData.long}
                    onChange={(e) => setProfileData({ ...profileData, long: e.target.value })}
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Landmark</label>
                <Input
                  value={profileData.landmark}
                  onChange={(e) => setProfileData({ ...profileData, landmark: e.target.value })}
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Service Radius *</label>
                <Input
                  type="number"
                  value={profileData.service_radius}
                  onChange={(e) => setProfileData({ ...profileData, service_radius: e.target.value })}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Profile Image</label>
                <Input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setProfileData({ ...profileData, profile_image: e.target.files?.[0] || null })}
                />
              </div>

              <div className="flex gap-3 pt-4">
                <Button type="button" onClick={onClose} variant="outline" className="flex-1">
                  Cancel
                </Button>
                <Button type="submit" className="flex-1" disabled={loading}>
                  {loading ? 'Saving...' : 'Save Profile'}
                </Button>
              </div>
            </form>
          )}

          {activeTab === 'agent' && (
            <form onSubmit={handleAgentConfigSubmit} className="space-y-4">
              <div className="bg-accent/5 border border-accent/20 rounded-lg p-4 mb-4">
                <p className="text-sm text-muted-foreground">
                  Configure the provider details used by the seller agent for job matching. These values will be used instead of fetching from the API.
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Average Rating (0-5) *</label>
                <Input
                  type="number"
                  min="0"
                  max="5"
                  step="0.1"
                  value={agentConfig.average_rating}
                  onChange={(e) => setAgentConfig({ ...agentConfig, average_rating: Number(e.target.value) })}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Total Completed Orders *</label>
                <Input
                  type="number"
                  min="0"
                  value={agentConfig.total_completed_order}
                  onChange={(e) => setAgentConfig({ ...agentConfig, total_completed_order: Number(e.target.value) })}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Number of Ratings</label>
                <Input
                  type="number"
                  min="0"
                  value={agentConfig.num_of_rating}
                  onChange={(e) => setAgentConfig({ ...agentConfig, num_of_rating: Number(e.target.value) })}
                />
              </div>

              <div>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={agentConfig.licensed}
                    onChange={(e) => setAgentConfig({ ...agentConfig, licensed: e.target.checked })}
                    className="rounded"
                  />
                  <span className="text-sm font-medium text-foreground">Licensed</span>
                </label>
              </div>

              <div className="flex gap-3 pt-4">
                <Button type="button" onClick={onClose} variant="outline" className="flex-1">
                  Cancel
                </Button>
                <Button type="submit" className="flex-1" disabled={loading}>
                  {loading ? 'Saving...' : 'Save Agent Config'}
                </Button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
