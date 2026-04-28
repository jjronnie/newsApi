<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $websites = [
            // Uganda
            'TheTechTower' => 'thetechtower.com',
            'MBU' => 'mbu.ug',
            'PC Tech Magazine' => 'pctechmag.com',
            'Diginited' => 'diginited.com',
            'Chimpreports Tech' => 'chimpreports.com',
            'SoftPower Tech' => 'softpower.ug',
            'Innovation Village' => 'innovation-village.com',
            'Nile Post Tech' => 'nilepost.co.ug',
            'Monitor Technology' => 'monitor.co.ug',
            'Observer Tech' => 'observer.ug',

            // East Africa
            'Techweez' => 'techweez.com',
            'TechTrends KE' => 'techtrends.co.ke',
            'Tech-ish' => 'tech-ish.com',
            'Disrupt Africa' => 'disruptafrica.com',
            'Capital FM Tech' => 'capitalfm.co.ke',
            'Business Daily Tech' => 'businessdailyafrica.com',
            'Nation Tech' => 'nation.africa',
            'The EastAfrican Tech' => 'theeastafrican.co.ke',
            'TechMoran' => 'techmoran.com',
            'Weetracker' => 'weetracker.com',

            // Africa-wide
            'TechCabal' => 'techcabal.com',
            'TechPoint Africa' => 'techpoint.africa',
            'Benjamin Dada' => 'benjamindada.com',
            'IT News Africa' => 'itnewsafrica.com',
            'Ventures Africa' => 'venturesafrica.com',
            'How We Made It in Africa' => 'howwemadeitinafrica.com',
            'African Business Tech' => 'africanbusiness.com',

            // Global
            'TechCrunch' => 'techcrunch.com',
            'The Verge' => 'theverge.com',
            'Wired' => 'wired.com',
            'Ars Technica' => 'arstechnica.com',
            'MIT Technology Review' => 'technologyreview.com',
            'BBC Technology' => 'bbc.com',
            'Reuters Technology' => 'reuters.com',
            'Bloomberg Technology' => 'bloomberg.com',
            'The Guardian Technology' => 'theguardian.com',
            'ZDNet' => 'zdnet.com',
        ];

        foreach ($websites as $name => $url) {
            Website::create([
                'name' => $name,
                'url' => $url,
            ]);
        }

        User::create([
            'name' => 'JRonnie',
            'email' => 'ronaldjjuuko7@gmail.com',
            'password' => bcrypt('88928892'),
            'email_verified_at' => now(),
        ]);
    }
}
