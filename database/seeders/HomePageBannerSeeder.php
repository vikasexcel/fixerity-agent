<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomePageBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $home_page_banner_record = [
            [
                'id' => 1,
                'service_id' => 19,
                'type' => 1,
                'service_name' => 'Home Cleaning',
                'banner_image' => '3473910202212022.jpg',
                'status' => '1',
                'name' => 'Need help to clean your home? Make your house clean and tidy with our house cleaning services.',
                'ja_name' => '家の掃除にお手伝いが必要ですか? 当社のハウスクリーニング サービスで家を清潔に整頓しましょう。',
                'pt_name' => 'Precisa de ajuda para limpar sua casa? Deixe sua casa limpa e arrumada com nossos serviços de limpeza doméstica.',
                'vi_name' => 'Bạn cần giúp dọn dẹp nhà cửa? Hãy làm cho ngôi nhà của bạn sạch sẽ và ngăn nắp với dịch vụ dọn dẹp nhà cửa của chúng tôi.',
                'he_name' => 'צריכים עזרה בניקיון הבית? הפוך את הבית שלך נקי ומסודר עם שירותי ניקיון הבית שלנו.',
                'de_name' => 'Brauchen Sie Hilfe bei der Reinigung Ihres Hauses? Sorgen Sie mit unseren Reinigungsdiensten dafür, dass Ihr Haus sauber und ordentlich ist.',
                'es_name' => '¿Necesitas ayuda para limpiar tu casa? Haz que tu casa esté limpia y ordenada con nuestros servicios de limpieza del hogar.',
                'fr_name' => 'Besoin d\'aide pour nettoyer votre maison ? Faites en sorte que votre maison soit propre et bien rangée grâce à nos services de nettoyage de maison.',
                'ko_name' => '집 청소에 도움이 필요하세요? 저희의 집 청소 서비스로 집을 깨끗하고 정돈되게 하세요.',
                'zh_name' => '需要帮忙打扫房屋吗？我们的房屋清洁服务可让您的房屋干净整洁。',
                'fil_name' => 'Kailangan mo ng tulong sa paglilinis ng iyong tahanan? Gawing malinis at maayos ang iyong bahay sa aming mga serbisyo sa paglilinis ng bahay.',
                'ar_name' => 'ar-name',
                'description' => 'Need help to clean your home? Make your house clean and tidy with our house cleaning services.',
                'ja_description' => '家の掃除にお手伝いが必要ですか? 当社のハウスクリーニング サービスで家を清潔に整頓しましょう。',
                'pt_description' => 'Precisa de ajuda para limpar sua casa? Deixe sua casa limpa e arrumada com nossos serviços de limpeza doméstica.',
                'vi_description' => 'Bạn cần giúp dọn dẹp nhà cửa? Hãy làm cho ngôi nhà của bạn sạch sẽ và ngăn nắp với dịch vụ dọn dẹp nhà cửa của chúng tôi.',
                'he_description' => 'צריכים עזרה בניקיון הבית? הפוך את הבית שלך נקי ומסודר עם שירותי ניקיון הבית שלנו.',
                'de_description' => 'Brauchen Sie Hilfe bei der Reinigung Ihres Hauses? Sorgen Sie mit unseren Reinigungsdiensten dafür, dass Ihr Haus sauber und ordentlich ist.',
                'es_description' => '¿Necesitas ayuda para limpiar tu casa? Haz que tu casa esté limpia y ordenada con nuestros servicios de limpieza del hogar.',
                'fr_description' => 'Besoin d\'aide pour nettoyer votre maison ? Faites en sorte que votre maison soit propre et bien rangée grâce à nos services de nettoyage de maison.',
                'ko_description' => '집 청소에 도움이 필요하세요? 저희의 집 청소 서비스로 집을 깨끗하고 정돈되게 하세요.',
                'zh_description' => '需要帮忙打扫房屋吗？我们的房屋清洁服务可让您的房屋干净整洁。',
                'fil_description' => 'Kailangan mo ng tulong sa paglilinis ng iyong tahanan? Gawing malinis at maayos ang iyong bahay sa aming mga serbisyo sa paglilinis ng bahay.',
                'ar_description' => 'ar-description'
            ],
            [
                'id' => 2,
                'service_id' => 19,
                'type' => 0,
                'service_name' => 'Home Cleaning',
                'banner_image' => '1183313202215068.jpg',
                'status' => '1',
                'name' => Null,
                'ja_name' => null,
                'pt_name' => null,
                'vi_name' => null,
                'he_name' => null,
                'de_name' => null,
                'es_name' => null,
                'fr_name' => null,
                'ko_name' => null,
                'zh_name' => null,
                'fil_name' => null,
                'ar_name' => Null,
                'description' => Null,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => Null
            ]
        ];
        /*
        | upsert
        |--------------------------------------------------------------------------
        | We are using upsert here as it functions to either insert or update records efficiently.
        | If a record already exists, it updates it; if not, it inserts a new record.
        | This operation compares records using a unique key and supports handling multiple records in a single operation.
        */
        DB::table('home_page_banner')->upsert(
            $home_page_banner_record,
            ['id'], // Unique column to determine if a row exists
            ['service_id', 'type', 'service_name', 'banner_image', 'status', 'name', 'ja_name', 'pt_name', 'vi_name', 'he_name',
                'de_name', 'es_name', 'fr_name', 'ko_name', 'zh_name', 'fil_name', 'ar_name', 'description', 'ja_description',
                'pt_description', 'vi_description', 'he_description', 'de_description', 'es_description', 'fr_description', 'ko_description', 'zh_description', 'fil_description', 'ar_description']
        );
    }
}
