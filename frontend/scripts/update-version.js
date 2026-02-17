import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Read package.json to get the version
const packageJson = JSON.parse(
  fs.readFileSync(path.resolve(__dirname, '../package.json'), 'utf-8')
);

const version = packageJson.version;

// Define all env files to update
const envFiles = ['.env', '.env.development', '.env.production'];

// Function to update a single env file
function updateEnvFile(envFileName) {
  const envPath = path.resolve(__dirname, '..', envFileName);
  
  let envContent = '';
  
  // Read existing .env file or create empty string
  if (fs.existsSync(envPath)) {
    envContent = fs.readFileSync(envPath, 'utf-8');
  }
  
  // Update or add VITE_APP_VERSION
  const versionRegex = /^VITE_APP_VERSION=.*/m;
  if (versionRegex.test(envContent)) {
    envContent = envContent.replace(versionRegex, `VITE_APP_VERSION=${version}`);
  } else {
    // Add to the end with a newline if file has content
    if (envContent && !envContent.endsWith('\n')) {
      envContent += '\n';
    }
    envContent += `VITE_APP_VERSION=${version}\n`;
  }
  
  // Write back to .env file
  fs.writeFileSync(envPath, envContent);
  
  console.log(`✅ Updated ${envFileName} - VITE_APP_VERSION=${version}`);
}

// Update all env files
envFiles.forEach(envFile => {
  try {
    updateEnvFile(envFile);
  } catch (error) {
    console.error(`⚠️  Failed to update ${envFile}:`, error.message);
  }
});

console.log(`\n🎉 All environment files updated to version ${version}`);