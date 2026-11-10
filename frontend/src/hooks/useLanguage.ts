import { useEffect, useState } from 'react';
import { en } from '../locales/en';
import { mm } from '../locales/mm';

export type AppLanguage = 'en' | 'mm';

export function useLanguage() {
  const [language, setLanguage] = useState<AppLanguage>(() => (localStorage.getItem('dw-language') as AppLanguage) || 'en');
  useEffect(() => { localStorage.setItem('dw-language', language); }, [language]);
  return { language, toggleLanguage: () => setLanguage((value) => value === 'en' ? 'mm' : 'en'), copy: language === 'mm' ? mm : en };
}
