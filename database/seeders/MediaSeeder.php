<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    const LOGO_1_ID = 'cccccccc-0000-0000-0000-000000000001';

    const BANNER_1_ID = 'cccccccc-0000-0000-0000-000000000002';

    const LOGO_2_ID = 'cccccccc-0000-0000-0000-000000000003';

    const BANNER_2_ID = 'cccccccc-0000-0000-0000-000000000004';

    public function run(): void
    {
        $records = [
            [
                'id' => self::LOGO_1_ID,
                'filename' => 'client-alpha-logo.png',
                'original_filename' => 'logo.png',
                'file_path' => 'media/logos/client-alpha-logo.png',
                'file_url' => 'https://res.cloudinary.com/demo/image/upload/client-alpha-logo.png',
                'file_size' => 24576,
                'mime_type' => 'image/png',
                'media_category' => 'logo',
                'cloudinary_public_id' => 'client-alpha-logo',
            ],
            [
                'id' => self::BANNER_1_ID,
                'filename' => 'client-alpha-banner.jpg',
                'original_filename' => 'banner.jpg',
                'file_path' => 'media/banners/client-alpha-banner.jpg',
                'file_url' => 'https://res.cloudinary.com/demo/image/upload/client-alpha-banner.jpg',
                'file_size' => 102400,
                'mime_type' => 'image/jpeg',
                'media_category' => 'banner',
                'cloudinary_public_id' => 'client-alpha-banner',
            ],
            [
                'id' => self::LOGO_2_ID,
                'filename' => 'client-beta-logo.png',
                'original_filename' => 'logo.png',
                'file_path' => 'media/logos/client-beta-logo.png',
                'file_url' => 'https://res.cloudinary.com/demo/image/upload/client-beta-logo.png',
                'file_size' => 18432,
                'mime_type' => 'image/png',
                'media_category' => 'logo',
                'cloudinary_public_id' => 'client-beta-logo',
            ],
            [
                'id' => self::BANNER_2_ID,
                'filename' => 'client-beta-banner.jpg',
                'original_filename' => 'banner.jpg',
                'file_path' => 'media/banners/client-beta-banner.jpg',
                'file_url' => 'https://res.cloudinary.com/demo/image/upload/client-beta-banner.jpg',
                'file_size' => 98304,
                'mime_type' => 'image/jpeg',
                'media_category' => 'banner',
                'cloudinary_public_id' => 'client-beta-banner',
            ],
        ];

        foreach ($records as $data) {
            Media::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Media seeded: 4 records (2 logos, 2 banners)');
    }
}
