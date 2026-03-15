<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResponseSeeder extends Seeder
{
    public function run()
    {
        // TARGET SURVEY ID (Jane Goodall's Qualitative Political Survey)
        $surveyId = 32; 

        // 1. Semantic Data Arrays for Real-World AI Testing
        $economy = [
            "The price of maize flour has doubled; I can't feed my family three meals anymore.",
            "Fuel prices are killing small businesses. I had to lay off two staff members this month.",
            "Rent is skyrocketing while my salary stays the same. The economy is broken for the youth.",
            "I've stopped using my car because of petrol costs. Public transport is now my only option.",
            "Everything is expensive! From soap to sugar, we are being taxed to death with no services."
        ];

        $healthcare = [
            "Hospitals have no medicine. You pay for a consultation only to be told to buy drugs at a private chemist.",
            "The recent upgrades to the local clinic are good, but we still need more doctors on duty.",
            "Waiting 8 hours to see a nurse is a sign that the system has collapsed.",
            "NHIF/Insurance is confusing. Half the private hospitals are now rejecting it.",
            "Maternal health has improved, but specialized care for the elderly is non-existent."
        ];

        $trust = [
            "I don't trust the IEBC. The servers are always 'too busy' when results start coming in.",
            "I believe the technology is good, but the people running it are easily bribed.",
            "The courts are the only reason I still have hope in the electoral process.",
            "Why vote? The winner is decided in a boardroom before we even cast our ballots.",
            "I trust the process as long as the international observers are present at every tallying center."
        ];

        $leader = [
            "I want a leader who has actually worked a normal job and understands the struggle.",
            "We need a visionary who prioritizes manufacturing and industry over empty rhetoric.",
            "A leader who is not afraid to jail their own corrupt friends and family members.",
            "I am looking for someone who will unite the country rather than playing tribal cards.",
            "We need a young, digital-savvy president who understands the global tech economy."
        ];

        $this->command->info('Injecting 500 realistic political responses into Survey 32...');

        // 2. Generate 500 Responses
        for ($i = 0; $i < 500; $i++) {
            // Create a Response entry (using the confirmed 'responses' table)
            $responseId = DB::table('responses')->insertGetId([
                'survey_id'     => $surveyId,
                'respondent_id' => null, // Anonymous
                'created_at'    => Carbon::now()->subDays(rand(1, 45)),
                'updated_at'    => Carbon::now(),
            ]);

            // 3. Prepare Answers JSON Object
            // The application expects an array of objects: [{"name": "...", "userData": "..."}]
            $answerData = [
                ['name' => 'respondent_id', 'userData' => 'Voter_' . rand(1000, 9999)],
                ['name' => 'primary_concern', 'userData' => collect(['economy', 'healthcare', 'corruption', 'infrastructure', 'education'])->random()],
                ['name' => 'voting_likelihood', 'userData' => collect(['certain', 'very_likely', 'neutral', 'unlikely'])->random()],
                ['name' => 'concern_impact_detail', 'userData' => collect([$economy, $healthcare, $trust])->random()[array_rand($economy)]],
                ['name' => 'cost_of_living_text', 'userData' => $economy[array_rand($economy)]],
                ['name' => 'healthcare_opinion_text', 'userData' => $healthcare[array_rand($healthcare)]],
                ['name' => 'ideal_leader_text', 'userData' => $leader[array_rand($leader)]],
                ['name' => 'opposition_policy_text', 'userData' => "The opposition claims they will lower taxes, but they don't explain how they will pay for roads."],
                ['name' => 'electoral_trust_text', 'userData' => $trust[array_rand($trust)]],
                ['name' => 'news_source_text', 'userData' => "I watch Citizen TV and follow KOT (Kenyans on Twitter) for the real ground updates."],
                ['name' => 'president_message_text', 'userData' => "Please, focus on the youth and stop the excessive external borrowing."],
                ['name' => 'vote_dealbreaker_text', 'userData' => "If they choose a running mate known for corruption, I am out."],
                ['name' => 'taxation_opinion_text', 'userData' => "High taxes with zero accountability is just legalized theft."],
                ['name' => 'country_direction', 'userData' => collect(['strongly_agree', 'agree', 'neutral', 'disagree', 'strongly_disagree'])->random()],
                ['name' => 'final_hopes_text', 'userData' => "I hope for a peaceful transition and lower flour prices."]
            ];

            // 4. Insert into the answers table (using the confirmed 'answers' table)
            DB::table('answers')->insert([
                'response_id' => $responseId,
                'question_id' => null, // Not used for JSON surveys
                'value'       => json_encode($answerData),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
            
            if ($i % 100 == 0 && $i > 0) {
                $this->command->info("Progress: $i/500...");
            }
        }

        $this->command->info('Successfully injected 500 realistic political responses!');
    }
}
