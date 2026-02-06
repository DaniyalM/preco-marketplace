import type { User } from '@/types';
import { http } from './client';

export interface MeResponse {
    id: string;
    email: string | null;
    name: string | null;
    roles: string[];
    is_admin: boolean;
    is_vendor: boolean;
    is_customer?: boolean;
}

export async function fetchMe(): Promise<MeResponse | null> {
    const res = await http.get<MeResponse | null>('/auth/me', {
        withCredentials: true,
    });
    return res.data;
}

/** Map API me response to User type */
export function mapMeToUser(data: MeResponse): User {
    return {
        id: data.id,
        email: data.email ?? null,
        name: data.name ?? null,
        given_name: null,
        family_name: null,
        username: null,
        email_verified: false,
        roles: data.roles ?? [],
        is_admin: data.is_admin ?? false,
        is_vendor: data.is_vendor ?? false,
        is_customer: data.is_customer ?? (!data.is_admin && !data.is_vendor),
    };
}

export async function refreshToken(): Promise<{
    success: boolean;
    expires_in?: number;
}> {
    const res = await http.post<{ success: boolean; expires_in?: number }>(
        '/auth/refresh'
    );
    return res.data;
}
