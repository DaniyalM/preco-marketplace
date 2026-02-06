import { useQuery } from '@tanstack/vue-query';
import { fetchMe } from '@/api/auth';
import { queryKeys } from '@/queries/keys';

export function useAuthMeQuery(options?: { enabled?: boolean }) {
    return useQuery({
        queryKey: queryKeys.authMe(),
        queryFn: fetchMe,
        enabled: options?.enabled !== false,
        staleTime: 1000 * 60 * 5, // 5 minutes
        retry: false,
    });
}
