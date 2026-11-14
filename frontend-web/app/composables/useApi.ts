import type { FetchOptions } from 'ofetch';

type ApiEnvelope<T> = { success: boolean; message: string; data: T };
type ApiBody = Record<string, unknown> | FormData;
type ApiOptions = Omit<FetchOptions, 'method' | 'body'> & {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: ApiBody;
};

/**
 * Klien REST API terpusat.
 * - Selalu memakai cookie httpOnly (credentials: 'include'), token tidak pernah
 *   disimpan di localStorage.
 * - Saat SSR, cookie request diteruskan ke backend agar halaman terautentikasi
 *   tetap bisa dirender di server.
 */
export function useApi() {
  const config = useRuntimeConfig();
  const baseURL = config.public.apiBase as string;

  async function call<T>(path: string, options: ApiOptions = {}): Promise<T> {
    const headers: Record<string, string> = { ...(options.headers as Record<string, string>) };
    if (import.meta.server) {
      const incoming = useRequestHeaders(['cookie']);
      if (incoming.cookie) headers.cookie = incoming.cookie;
    }

    const response = await $fetch<ApiEnvelope<T>>(path, {
      baseURL,
      credentials: 'include',
      headers,
      ...options,
    });
    return response.data;
  }

  return {
    get: <T>(path: string, query?: Record<string, unknown>) => call<T>(path, { method: 'GET', query }),
    post: <T>(path: string, body?: ApiBody) => call<T>(path, { method: 'POST', body }),
    patch: <T>(path: string, body?: ApiBody) => call<T>(path, { method: 'PATCH', body }),
    del: <T>(path: string, body?: ApiBody) => call<T>(path, { method: 'DELETE', body }),
    /** Unggah file: FormData dikirim apa adanya agar Multer bisa membacanya. */
    upload: <T>(path: string, form: FormData) => call<T>(path, { method: 'POST', body: form }),
    baseURL,
  };
}

/** Ambil pesan error yang ramah dari kegagalan API. */
export function apiError(error: unknown, fallback = 'Terjadi kesalahan. Coba lagi.'): string {
  const data = (error as { data?: { message?: string } })?.data;
  if (data?.message) return data.message;
  const message = (error as { message?: string })?.message;
  if (message && !message.includes('fetch')) return message;
  return fallback;
}
