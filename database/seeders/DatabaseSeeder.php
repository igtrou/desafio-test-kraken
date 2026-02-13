<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Quotation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Executa o processo configurado.
     */
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $assets = collect([
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'type' => 'crypto'],
            ['symbol' => 'ETH', 'name' => 'Ethereum', 'type' => 'crypto'],
            ['symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'type' => 'stock'],
            ['symbol' => 'USDBRL', 'name' => 'USD/BRL', 'type' => 'currency'],
        ])->mapWithKeys(function (array $payload): array {
            $asset = Asset::query()->updateOrCreate(
                ['symbol' => $payload['symbol']],
                $payload
            );

            return [$asset->symbol => $asset];
        });

        // Keep seeded quotations in a realistic and always-past window (middle of previous month).
        $midMonth = CarbonImmutable::now('UTC')
            ->subMonthNoOverflow()
            ->startOfMonth()
            ->addDays(14)
            ->setTime(10, 0, 0);
        $quotedAtMoments = [
            $midMonth->subDays(2),
            $midMonth->subDay(),
            $midMonth,
            $midMonth->addDay(),
            $midMonth->addDays(2),
        ];

        $quotationMatrix = [
            'BTC' => [
                'source' => 'awesome_api',
                'currency' => 'USD',
                'prices' => [68120.45, 68690.12, 69310.84, 68987.22, 69555.90],
            ],
            'ETH' => [
                'source' => 'awesome_api',
                'currency' => 'USD',
                'prices' => [2011.34, 2055.77, 2088.44, 2079.61, 2102.09],
            ],
            'MSFT' => [
                'source' => 'yahoo_finance',
                'currency' => 'USD',
                'prices' => [398.21, 401.43, 404.09, 402.88, 406.12],
            ],
            'USDBRL' => [
                'source' => 'awesome_api',
                'currency' => 'BRL',
                'prices' => [5.1812, 5.1945, 5.2089, 5.2011, 5.2177],
            ],
        ];

        foreach ($quotationMatrix as $symbol => $payload) {
            /** @var Asset|null $asset */
            $asset = $assets->get($symbol);

            if (! $asset instanceof Asset) {
                continue;
            }

            foreach ($quotedAtMoments as $index => $quotedAt) {
                Quotation::query()->updateOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'source' => (string) $payload['source'],
                        'currency' => (string) $payload['currency'],
                        'quoted_at' => $quotedAt->toDateTimeString(),
                    ],
                    [
                        'price' => (float) $payload['prices'][$index],
                        'status' => Quotation::STATUS_VALID,
                        'invalid_reason' => null,
                        'invalidated_at' => null,
                    ]
                );
            }
        }
    }
}
