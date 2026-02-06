import { useQuery } from '@tanstack/vue-query';
import { fetchVendorDashboard } from '@/api/vendor';
import { queryKeys } from '@/queries/keys';

export function useVendorDashboardQuery() {
    return useQuery({
        queryKey: queryKeys.vendor.dashboard(),
        queryFn: fetchVendorDashboard,
    });
}
