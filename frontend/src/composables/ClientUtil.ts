
// 👉 Function to resolve client type variant for display
export const resolveClientTypeVariant = (clientType: string | null) => {
  const typeLowerCase = (clientType || '').toLowerCase();

  if (typeLowerCase === 'particulier')
    return { color: 'success', icon: 'tabler-user' };
  if (typeLowerCase === 'entreprise')
    return { color: 'error', icon: 'tabler-building' };
  if (typeLowerCase === 'institution')
    return { color: 'info', icon: 'tabler-university' };
  return { color: 'primary', icon: 'tabler-user' };
};

// 👉 Function to resolve client status variant for display
export const resolveClientStatusVariant = (status: string | null) => {
  const statusLowerCase = (status || '').toLowerCase();
  if (statusLowerCase === 'actif')
    return 'success';
  if (statusLowerCase === 'inactif')
    return 'primary';
  if (statusLowerCase === 'en litige')
    return 'warning';
  return 'primary';
};

// 👉 Function to resolve client gamut variant for display
export const resolveClientGamutVariant = (gamut: string | null) => {
  const gamutLowerCase = (gamut || '').toLowerCase();

  if (gamutLowerCase === 'bronze')
    return '#CE8946';
  if (gamutLowerCase === 'silver')
    return ' #C0C0C0';
  if (gamutLowerCase === 'gold')
    return '#FFD700';
  if (gamutLowerCase === 'platinium')
    return '#A8A9AD';


  return 'primary';
};

export const mapStaticData = (
  items: { display? : string;name?: string; fullName?: string; legalName?: string; title? : string; id: string; ice?: string; code?: string , stauts?: string }[] = []
) => {
  return items.map((item: {display? : string; name?: string; fullName?: string; legalName?: string; title? : string; id: string; ice?: string,code?: string , status? : string }) => ({
    text: item.display || item.name || item.fullName || item.legalName || item.title || item.code || "", 
    value: item.id, 
    ice: item.ice,
    status : item.status,
  }));
};


export function getColor(type: 'sector' | 'gamut', value: string, index?: number): string {
  if (type === 'sector') {
    const fixedColors = ['#FF5733', '#33B5E5', '#AA66CC', '#99CC00', '#FFBB33', '#FF4444']; 
    return fixedColors[(index ?? 0) % fixedColors.length];
  } else if (type === 'gamut') {
    switch (value) {
      case 'Platinium':
        return '#A8A9AD';
      case 'Gold':
        return '#FFD700';
      case 'Silver':
        return '#C0C0C0';
      case 'Bronze':
        return '#CE8946';
      default:
        return 'grey';
    }
  }
  return 'grey';
}


