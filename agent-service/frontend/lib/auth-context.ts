export interface AuthSession {
  user: {
    id: string;
    email: string;
    name: string;
    role: 'buyer' | 'seller';
    avatar?: string;
  } | null;
  isLoading: boolean;
}

// In-memory storage for auth state
let currentSession: AuthSession = {
  user: null,
  isLoading: false,
};

export function getAuthSession(): AuthSession {
  return currentSession;
}

export function setAuthSession(session: AuthSession): void {
  currentSession = session;
}

export function login(email: string, password: string, role: 'buyer' | 'seller') {
  // Dummy authentication - in real app, this would call an API
  const user = {
    id: role === 'buyer' ? 'user_1' : 'user_2',
    email,
    name: role === 'buyer' ? 'Sarah Chen' : 'Marcus Johnson',
    role,
    avatar: undefined,
  };

  currentSession = {
    user,
    isLoading: false,
  };

  return user;
}

export function logout() {
  currentSession = {
    user: null,
    isLoading: false,
  };
}

export function signup(email: string, name: string, password: string, role: 'buyer' | 'seller') {
  // Dummy signup - in real app, this would create a new user
  const user = {
    id: Math.random().toString(36).substr(2, 9),
    email,
    name,
    role,
    avatar: undefined,
  };

  currentSession = {
    user,
    isLoading: false,
  };

  return user;
}
