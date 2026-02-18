import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: "https://print-backend-devops-production.up.railway.app/api" || '/api',
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
