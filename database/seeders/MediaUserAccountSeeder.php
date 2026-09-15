<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Media;
use App\Models\MediaCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MediaUserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mediaList = [
            'analitiknews.com', 'sorotmata.id', 'politikal.id', 'publikkaltim.com',
            'pojoknegeri.com', 'idenesia.co', 'diksi.co', 'popnews.id',
            'presisi.co', 'cakrawalakaltim.com', 'linimasa.co', 'harianrakyat.co',
            'bekesah.co', 'editorialkaltim.com', 'pusaranmedia.com', 'samarindasmartcity.com',
            'sketsa.id', 'metronews.co', 'timesindonesia.co.id', 'apakabar.co',
            'acuankaltim.com', 'kaltimnow.id', 'suluhmudanusantara.com', 'metrokaltim.com',
            'divisi.id', 'newsbalikpapan.com', 'titiknol.id', 'afiliasi.net',
            'borneoflash.com', 'radiuskaltim.com', 'narasi', 'garudasatu',
            'prokal.co', 'kaltimnusantara', 'niagaasia', 'faktaborneo',
            'kaltimnewsroom', 'ujarku', 'garispena', 'lensaborneo',
            'harianborneo', 'kliksamarinda', 'gosamarinda', 'infosatu.co',
            'infobenua', 'catatan.co', 'busam.id', 'kosmopolitan.id',
            'metroikn', 'portalborneo', 'kaltimpedia', 'kaltimmedia',
            'adakah.id', 'katuju', 'beri.id', 'arusbawah',
            'avnmedia.id', 'hariankaltim', 'ulasankaltim', 'locerita',
            'lenteradjoeang', 'mahakamdaily', 'kompak.id', 'kaltimvoice.id',
            'katamedia', 'nukaltim', 'pantaukaltim', 'kabarborneo',
            'voxborneo', 'solidaritasnews', 'prolog', 'klikmerdeka',
            'perkata.id', 'seputarnusantara.net', 'mediasatya.co', 'atlasnusantara',
            'mediamasa.id', 'sapakaltim', 'adakata.id', 'beritakaltim',
            'inewssamarinda', 'mediakaltim', 'beritanusantara', 'halamankanan',
            'vivanusantara'
        ];

        $password = Hash::make('Samarinda1@');
        
        $category = MediaCategory::firstOrCreate(
            ['name' => 'Media Online'],
            ['description' => 'Kategori Media Online']
        );

        foreach ($mediaList as $mediaName) {
            $email = strtolower(str_replace(' ', '', $mediaName)) . '@simpati.id';
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $mediaName,
                    'password' => $password,
                ]
            );

            // Assign role if it doesn't have it
            if (!$user->hasRole('media_partner')) {
                $user->assignRole('media_partner');
            }

            // Create Media Profile
            Media::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'media_category_id' => $category->id,
                    'company_name' => 'PT ' . ucfirst($mediaName),
                    'brand_name' => $mediaName,
                    'website' => 'https://' . $mediaName,
                    'address' => '-',
                    'phone' => '-',
                    'email' => $email,
                    'director' => '-',
                    'chief_editor' => '-',
                    'description' => 'Media Partner ' . $mediaName,
                    'verification_status' => 'draft', // Or 'approved' based on needs
                    'verification_score' => 0,
                    'completeness_percentage' => 0,
                ]
            );
        }
    }
}
