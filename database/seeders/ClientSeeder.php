<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    const CLIENT_ALPHA_ID = 'dddddddd-0000-0000-0000-000000000001';
    const CLIENT_BETA_ID  = 'dddddddd-0000-0000-0000-000000000002';

    public function run(): void
    {
        $clients = [
            [
                'id'                   => self::CLIENT_ALPHA_ID,
                'code'                 => 'CLT-0001',
                'company_code'         => 'REG-KH-2024-001',
                'company_name'         => 'Alpha Tech Solutions',
                'phone_number'         => '+85523456789',
                'email'                => 'contact@alphatech.kh',
                'headquarter_address'  => 'Street 271, Sangkat Toul Tum Poung, Khan Chamkarmon, Phnom Penh',
                'social_links'         => json_encode([
                    'facebook'  => 'https://facebook.com/alphatech',
                    'linkedin'  => 'https://linkedin.com/company/alphatech',
                    'telegram'  => 'https://t.me/alphatech',
                ]),
                'is_active'            => true,
                'assigned_sale_id'     => DemoUserSeeder::SALE_USER_ID,
                'logo_image_id'        => MediaSeeder::LOGO_1_ID,
                'banner_image_id'      => MediaSeeder::BANNER_1_ID,
            ],
            [
                'id'                   => self::CLIENT_BETA_ID,
                'code'                 => 'CLT-0002',
                'company_code'         => 'REG-KH-2024-002',
                'company_name'         => 'Beta Logistics Group',
                'phone_number'         => '+85512987654',
                'email'                => 'info@betalogistics.com',
                'headquarter_address'  => 'National Road 6A, Russei Keo, Phnom Penh',
                'social_links'         => json_encode([
                    'facebook' => 'https://facebook.com/betalogistics',
                    'telegram' => 'https://t.me/betalogistics',
                ]),
                'is_active'            => true,
                'assigned_sale_id'     => DemoUserSeeder::SALE_USER_ID,
                'logo_image_id'        => MediaSeeder::LOGO_2_ID,
                'banner_image_id'      => MediaSeeder::BANNER_2_ID,
            ],
        ];

        foreach ($clients as $data) {
            Client::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Clients seeded: Alpha Tech Solutions (CLT-0001), Beta Logistics Group (CLT-0002)');
    }
}
