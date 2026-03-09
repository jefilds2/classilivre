import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'br.com.classilivre.autos',
  appName: 'Classi Livre Autos',
  webDir: 'www',
  bundledWebRuntime: false,
  server: {
    url: 'https://classilivreautos.com',
    allowNavigation: ['classilivreautos.com', '*.classilivreautos.com'],
    cleartext: true,
  },
  android: {
    allowMixedContent: true,
  },
};

export default config;
