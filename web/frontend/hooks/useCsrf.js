import { useAuthenticatedFetch } from './useAuthenticatedFetch'

export const useCsrf = () => {
  const fetch = useAuthenticatedFetch()

  return async () => {
    const response = await fetch('/api/csrf-token')
    const { csrf_token } = await response.json()

    return csrf_token
  }
}
