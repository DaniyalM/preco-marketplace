<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Decentralized Payment – Supported Networks
    |--------------------------------------------------------------------------
    |
    | Each network has a chain ID, native currency, and optional stablecoin.
    | Marketplace wallet addresses receive crypto payments; set via env.
    |
    */
    'networks' => [
        'ethereum' => [
            'chain_id' => 1,
            'name' => 'Ethereum',
            'native_currency' => 'ETH',
            'decimals' => 18,
            'explorer_tx' => 'https://etherscan.io/tx/',
            'wallet_address' => env('BLOCKCHAIN_ETHEREUM_WALLET'),
        ],
        'polygon' => [
            'chain_id' => 137,
            'name' => 'Polygon',
            'native_currency' => 'MATIC',
            'decimals' => 18,
            'explorer_tx' => 'https://polygonscan.com/tx/',
            'wallet_address' => env('BLOCKCHAIN_POLYGON_WALLET'),
        ],
        'sepolia' => [
            'chain_id' => 11155111,
            'name' => 'Sepolia (Testnet)',
            'native_currency' => 'ETH',
            'decimals' => 18,
            'explorer_tx' => 'https://sepolia.etherscan.io/tx/',
            'wallet_address' => env('BLOCKCHAIN_SEPOLIA_WALLET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | USD to Crypto Conversion (Optional)
    |--------------------------------------------------------------------------
    | For display only. In production use an oracle or API for live rates.
    */
    'usd_to_crypto' => [
        'ETH' => (float) env('BLOCKCHAIN_USD_PER_ETH', 2500),
        'MATIC' => (float) env('BLOCKCHAIN_USD_PER_MATIC', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default network for new orders (key in networks array)
    |--------------------------------------------------------------------------
    */
    'default_network' => env('BLOCKCHAIN_DEFAULT_NETWORK', 'polygon'),

];
