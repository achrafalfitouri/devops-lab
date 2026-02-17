import logoData from './logo.txt?raw';

export const LOGO_BASE64 = `data:image/png;base64,${logoData.trim()}`;

// ============ LOGO SETTINGS ============
export const LOGO_WIDTH = 310;   
export const LOGO_HEIGHT = 170;   
export const LOGO_X = -55;        
export const LOGO_Y = -5;        

// ============ DIVIDER SETTINGS ============
export const SHOW_DIVIDER = true;        
export const DIVIDER_X = 40;                
export const DIVIDER_WIDTH = 120;           
export const DIVIDER_MARGIN_TOP = -15;       
export const DIVIDER_THICKNESS = 0.5;       

// ============ SPACING SETTINGS ============
export const SPACE_AFTER_DIVIDER = 25;      