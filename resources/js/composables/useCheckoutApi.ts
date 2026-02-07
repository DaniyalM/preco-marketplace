import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import {
    fetchBlockchainNetworks,
    placeOrder,
    confirmCryptoPayment,
    type PlaceOrderInput,
} from '@/api/checkout';
import { queryKeys } from '@/queries/keys';

export function useBlockchainNetworksQuery() {
    return useQuery({
        queryKey: ['checkout', 'blockchain-networks'],
        queryFn: fetchBlockchainNetworks,
    });
}

export function usePlaceOrderMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (input: PlaceOrderInput) => placeOrder(input),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.cart() });
        },
    });
}

export function useConfirmCryptoPaymentMutation() {
    return useMutation({
        mutationFn: ({
            orderId,
            txHash,
            payerWalletAddress,
        }: {
            orderId: number;
            txHash: string;
            payerWalletAddress: string;
        }) => confirmCryptoPayment(orderId, txHash, payerWalletAddress),
    });
}
