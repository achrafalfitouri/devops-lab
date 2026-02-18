import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: (window as any).__ENV__?.API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    if (accessToken) {
      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${accessToken}`,
      }
    }
  },
})
