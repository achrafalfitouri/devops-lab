import { useAuthStore } from '@/stores/auth';

export async function authGuard(to: any, from: any, next: any) {
  const authStore = useAuthStore();
  try {
    if (to.name === 'login') {
      next();
      return;
    }

    if (!authStore.isAuthenticated) {
      console.warn('[AuthGuard] User not authenticated. Redirecting to login.');
      await authStore.logout();
      next({ name: 'login' });
      return;
    }

    if (to.path === '/' || to.name === 'index' || to.name === 'home') {
      const userId = authStore.user?.id;
      const accessibleRoute = getFirstAccessibleRoute(authStore.permissions, userId);
      next(accessibleRoute);
      return;
    }

    const permissionConfig = getRequiredPermissionsForRoute(to.name);
    const { permissions: requiredPermissions, logic } = permissionConfig;

    let hasPermissions = false;

    if (!requiredPermissions || requiredPermissions.length === 0 || requiredPermissions.includes('')) {
      hasPermissions = true;
    } else if (logic === 'AND') {
      hasPermissions = requiredPermissions.every(permission =>
        authStore.permissions.includes(permission)
      );
    } else {
      hasPermissions = requiredPermissions.some(permission =>
        authStore.permissions.includes(permission)
      );
    }

    if (hasPermissions) {
      next();
    } else {
      console.error(`[AuthGuard] Permission denied for: ${to.name}. Redirecting to error page.`);
      next({ name: '$error', params: { error: 403 } });
    }
  } catch (error) {
    console.error('[AuthGuard] Unexpected error:', error);
    next({ name: '$error', params: { error: 500 } });
  }
}

const getFirstAccessibleRoute = (userPermissions: string[], userId?: string | number): string => {
  const routePriority = [
    'users',
    'clients',
    'cashregisters',
    'payments',
    'documents',
    'email',
    'logs',
    'archives'
  ];

  for (const routeName of routePriority) {
    const permissionConfig = getRequiredPermissionsForRoute(routeName);
    if (hasRequiredPermissions(userPermissions, permissionConfig)) {
      return `/${routeName}`;
    }
  }

  if (userId) {
    const profilePath = `/profil/${userId}`;
    return profilePath;
  }

  return '/profil';
};
