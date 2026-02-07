import { getData, http } from './client';

export interface BlockchainNetwork {
    key: string;
    name: string;
    chain_id: number;
    native_currency: string;
    decimals: number;
}

export interface ShippingAddress {
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state?: string;
    postal_code: string;
    country: string;
}

export interface PlaceOrderInput {
    shipping_address: ShippingAddress;
    payment_method: 'blockchain' | 'card';
    payment_network?: string;
    customer_notes?: string;
}

export interface BlockchainPaymentInfo {
    network: string;
    chain_id: number;
    currency: string;
    amount_crypto: string;
    amount_usd: number;
    merchant_wallet_address: string | null;
    explorer_tx_url: string | null;
}

export interface OrderData {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    payment_method: string;
    payment_reference: string | null;
    payment_network: string | null;
    payment_chain_id: number | null;
    payment_currency: string | null;
    payer_wallet_address: string | null;
    subtotal: number;
    total: number;
    currency: string;
    paid_at: string | null;
    created_at: string;
    blockchain_payment?: BlockchainPaymentInfo;
}

export async function fetchBlockchainNetworks(): Promise<BlockchainNetwork[]> {
    const res = await http.get<{ data: BlockchainNetwork[] }>('/api/checkout/blockchain-networks');
    return res.data.data;
}

export async function placeOrder(input: PlaceOrderInput): Promise<OrderData> {
    const res = await http.post<{ data: OrderData }>('/api/checkout/order', input);
    return res.data.data;
}

export async function confirmCryptoPayment(
    orderId: number,
    txHash: string,
    payerWalletAddress: string
): Promise<OrderData> {
    const res = await http.post<{ data: OrderData }>(
        `/api/checkout/orders/${orderId}/confirm-crypto`,
        { tx_hash: txHash, payer_wallet_address: payerWalletAddress }
    );
    return res.data.data;
}
