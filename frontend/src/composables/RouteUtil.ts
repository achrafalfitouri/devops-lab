
interface PermissionConfig {
  permissions: string[];
  logic: 'OR' | 'AND';
}

export const getRequiredPermissionsForRoute = (routeName: string): PermissionConfig => {
  const routePermissions: { [key: string]: PermissionConfig } = {
    users: { 
      permissions: ['user_manager', 'user_viewer'], 
      logic: 'OR' 
    },
    clients: { 
      permissions: ['client_manager', 'client_viewer'], 
      logic: 'OR' 
    },
    cashregisters: { 
      permissions: ['cashregister_manager', 'cashregister_viewer', 'transaction_manager'], 
      logic: 'OR' 
    },
    email: { 
      permissions: ['email_manager'], 
      logic: 'OR' 
    },
    payments: { 
      permissions: ['payment_manager', 'payment_viewer'], 
      logic: 'OR' 
    },
    documents: { 
      permissions: ['document_manager', 'document_viewer'], 
      logic: 'OR' 
    },
    logs: { 
      permissions: ['logs_viewer'], 
      logic: 'OR' 
    },
    archives: { 
      permissions: ['archive_viewer'], 
      logic: 'OR' 
    },
    profil: { 
      permissions: [''], 
      logic: 'OR' 
    }
  };

  return routePermissions[routeName] || { permissions: [], logic: 'OR' };
}

export const getRequiredPermissionsForNavItem = (navTitle: string): PermissionConfig => {
  const navPermissions: { [key: string]: PermissionConfig } = {
    'Utilisateurs': { 
      permissions: ['user_manager', 'user_viewer'], 
      logic: 'OR' 
    },
    'Clients': { 
      permissions: ['client_manager', 'client_viewer'], 
      logic: 'OR' 
    },
    'Caisses': { 
      permissions: ['cashregister_manager', 'cashregister_viewer', 'transaction_manager'], 
      logic: 'OR' 
    },
    'Emails': { 
      permissions: ['email_manager'], 
      logic: 'OR' 
    },
    'Paiements': { 
      permissions: ['payment_manager', 'payment_viewer'], 
      logic: 'OR' 
    },
    'Documents': { 
      permissions: ['document_manager', 'document_viewer'], 
      logic: 'OR' 
    },
    'Logs': { 
      permissions: ['logs_viewer'], 
      logic: 'OR' 
    },
    'Archives': { 
      permissions: ['archive_viewer'], 
      logic: 'OR' 
    },
    'Profil': { 
      permissions: [''], 
      logic: 'OR' 
    }
  };

  return navPermissions[navTitle] || { permissions: [], logic: 'OR' };
}

export const hasRequiredPermissions = (
  userPermissions: string[], 
  requiredConfig: PermissionConfig
): boolean => {
  const { permissions: requiredPermissions, logic } = requiredConfig;

  if (!requiredPermissions || requiredPermissions.length === 0 || requiredPermissions.includes('')) {
    return true;
  }

  if (logic === 'AND') {
    return requiredPermissions.every(permission => 
      userPermissions.includes(permission)
    );
  } else {
    return requiredPermissions.some(permission => 
      userPermissions.includes(permission)
    );
  }
}
