// 👉 Function to resolve user role variant for display
export const resolveUserRoleVariant = (userRole: string | null) => {
  const roleLowerCase = (userRole || '').toLowerCase()
  if (roleLowerCase === 'subscriber')
    return { color: 'success', icon: 'tabler-user' }
  if (roleLowerCase === 'author')
    return { color: 'error', icon: 'tabler-device-desktop' }
  if (roleLowerCase === 'maintainer')
    return { color: 'info', icon: 'tabler-chart-pie' }
  if (roleLowerCase === 'editor')
    return { color: 'warning', icon: 'tabler-edit' }
  if (roleLowerCase === 'admin')
    return { color: 'primary', icon: 'tabler-crown' }
  return { color: 'primary', icon: 'tabler-user' }
}

// 👉 Function to resolve status variant for VChip
// 👉 Function to get variant for user status
export const resolveUserStatusVariant = (
  stat: any,
  passwordRequestCheck?: any,
) => {
  if (passwordRequestCheck === true || passwordRequestCheck === 1) {
    return 'primary';
  }

  if (stat === true || stat === 1) {
    return 'success';
  }

  if (stat === false || stat === 0) {
    return 'primary';
  }

  return 'default';
};

// 👉 Function to get human-readable status
export const getStatusTitle = (
  stat: any,
  passwordRequestCheck?: any,
) => {
  if (passwordRequestCheck === true || passwordRequestCheck === 1) {
    return 'Mot de passe';
  }

  if (stat === true || stat === 1) {
    return 'Activé';
  }

  if (stat === false || stat === 0) {
    return 'Désactivé';
  }

  return 'Unknown';
};



export const getStatusIsTaxable = (stat: number | boolean) => {
  if (stat === true || stat === 1) {
    return 'Oui';
  }
  if (stat === false || stat === 0) {
    return 'Non';
  }
  return 'Inconnu'; 
};
