import axios, { type AxiosInstance } from 'axios';

/**
 * Central HTTP client. All API calls go through this instance.
 * Credentials (cookies) are sent for same-origin requests.
 */
export const http: AxiosInstance = axios.create({
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/** Extract API response data; Laravel often returns { data: T } */
export async function getData<T>(promise: Promise<{ data: T }>): Promise<T> {
    const res = await promise;
    return res.data;
}
