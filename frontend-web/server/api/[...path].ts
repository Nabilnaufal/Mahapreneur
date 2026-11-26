import { getRouterParam, proxyRequest } from 'h3';

/**
 * Proxy API same-origin untuk production.
 * Cookie dari Laravel diteruskan ke browser melalui domain frontend Nuxt,
 * sehingga auth tetap terbaca pada SSR dan navigasi client.
 */
export default defineEventHandler((event) => {
  const config = useRuntimeConfig(event);
  const path = getRouterParam(event, 'path') ?? '';
  const target = `${String(config.apiOrigin).replace(/\/$/, '')}/api/${path}`;

  return proxyRequest(event, target);
});
