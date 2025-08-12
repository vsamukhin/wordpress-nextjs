/* eslint-disable @typescript-eslint/no-explicit-any */
export { };

declare global {
  interface Window {
    jQuery: any;
    elementorFrontend: {
      init?: () => void;
      hooks?: any;
      [key: string]: any;
    };
  }
}
