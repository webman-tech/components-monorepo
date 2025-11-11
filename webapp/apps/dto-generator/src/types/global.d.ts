export {};

declare global {
  interface Window {
    __DTO_GENERATOR_CONFIG?: {
      defaultGenerationType?: 'dto' | 'form';
      defaultNamespace?: string;
    };
  }
}
