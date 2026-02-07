# Blockchain / Decentralized Payments

The platform supports **decentralized crypto payments** so customers can pay with cryptocurrency (ETH, MATIC, etc.) without a central payment processor.

## How it works

1. **Checkout**: Customer selects "Decentralized (Crypto)" and a network (e.g. Polygon, Ethereum or Sepolia testnet).
2. **Place order**: An order is created with status `pending` and `payment_status` `pending`. The response includes the amount in crypto and the **merchant wallet address**.
3. **Customer pays**: From their own wallet (MetaMask, etc.) they send the exact crypto amount to the merchant address.
4. **Confirm payment**: Customer submits the **transaction hash** and their **wallet address**. The order is marked `payment_status = paid`. (Optional: backend job can verify the tx on-chain later.)

No custody: funds go directly to your configured wallet. The marketplace never holds customer crypto.

## Configuration

1. **Run the migration** (adds blockchain fields to `orders`):

   ```bash
   php artisan migrate
   ```

2. **Set wallet addresses** in `.env` (one per network you want to accept):

   ```env
   BLOCKCHAIN_ETHEREUM_WALLET=0x...
   BLOCKCHAIN_POLYGON_WALLET=0x...
   BLOCKCHAIN_SEPOLIA_WALLET=0x...   # Testnet for development
   BLOCKCHAIN_DEFAULT_NETWORK=polygon
   # Optional: USD per token for displaying crypto amount (use an oracle in production)
   BLOCKCHAIN_USD_PER_ETH=2500
   BLOCKCHAIN_USD_PER_MATIC=0.5
   ```

3. Only networks with a non-empty wallet address are shown at checkout. Set at least one of the wallet variables to enable crypto payments.

## API

- `GET /api/checkout/blockchain-networks` – List supported networks (chain_id, name, native currency). Requires auth.
- `POST /api/checkout/order` – Create order from cart. Body: `shipping_address`, `payment_method` (`blockchain` | `card`), optional `payment_network`. For `blockchain`, response includes `blockchain_payment` (amount_crypto, merchant_wallet_address, etc.).
- `POST /api/checkout/orders/{order}/confirm-crypto` – Confirm crypto payment. Body: `tx_hash`, `payer_wallet_address`. Requires order to belong to current user.

## Order fields (blockchain)

- `payment_method`: `blockchain`
- `payment_reference`: transaction hash (after confirm)
- `payment_network`: e.g. `ethereum`, `polygon`, `sepolia`
- `payment_chain_id`: e.g. `1`, `137`, `11155111`
- `payment_currency`: e.g. `ETH`, `MATIC`
- `payer_wallet_address`: customer wallet (after confirm)

## Security notes

- Configure only wallet addresses you control. Never commit private keys or secrets.
- For production, consider verifying transactions on-chain (e.g. via a queue job) before or after marking as paid.
- Use Sepolia (or another testnet) for development; set `BLOCKCHAIN_SEPOLIA_WALLET` and use Sepolia in the checkout network selector.
