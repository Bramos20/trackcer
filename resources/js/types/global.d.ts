import { AxiosInstance } from 'axios';

declare global {
  function route(name: string, params?: any): string;
  interface Window {
    axios: AxiosInstance;
  }
}

declare module '*.svg' {
  const content: string;
  export default content;
}

export {};