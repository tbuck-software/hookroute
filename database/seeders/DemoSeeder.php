<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Delivery;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->where('email', 'demo@hookroute.test')->first()?->delete();

        $referenceTime = CarbonImmutable::now();
        $user = User::factory()->create([
            'name' => 'Torben Buck',
            'email' => 'demo@hookroute.test',
            'password' => Hash::make('password'),
        ]);
        $project = Project::factory()->for($user, 'owner')->create([
            'name' => 'Production systems',
            'slug' => 'production-systems',
        ]);
        $project->members()->attach($user, ['role' => 'owner']);

        $github = Source::factory()->for($project)->create(['name' => 'GitHub', 'slug' => 'github', 'last_received_at' => $referenceTime->subMinutes(4)]);
        $billing = Source::factory()->for($project)->create(['name' => 'Billing API', 'slug' => 'billing', 'last_received_at' => $referenceTime->subMinutes(18)]);
        $discord = Destination::factory()->for($project)->discord()->create(['name' => 'Operations Discord', 'last_delivered_at' => $referenceTime->subMinutes(4)]);
        $archive = Destination::factory()->for($project)->webhook()->create(['name' => 'Audit archive', 'last_delivered_at' => $referenceTime->subMinutes(8)]);
        $digest = Destination::factory()->for($project)->digest()->create(['name' => 'Evening summary']);
        $githubDiscord = Connection::factory()->for($project)->for($github)->for($discord)->create(['name' => 'GitHub → Discord', 'payload_mode' => 'template', 'body_template' => '**{{ payload.repository }}** · {{ payload.action }}']);
        $githubArchive = Connection::factory()->for($project)->for($github)->for($archive)->create(['name' => 'GitHub → archive']);
        Connection::factory()->for($project)->for($billing)->for($digest)->create(['name' => 'Billing → daily digest']);

        foreach (range(1, 9) as $index) {
            $source = $index % 3 === 0 ? $billing : $github;
            $payload = $source->is($github)
                ? ['repository' => 'tbuck/atlas', 'action' => $index % 2 ? 'push' : 'pull_request', 'ref' => 'main']
                : ['invoice' => 4100 + $index, 'status' => 'paid', 'amount' => 49.90];
            $event = Event::factory()->for($project)->for($source)->create([
                'public_id' => sprintf('01JHKR000000000000000000%02d', $index),
                'payload' => $payload,
                'raw_body' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'received_at' => $referenceTime->subMinutes($index * 7),
            ]);
            if ($source->is($github)) {
                foreach ([$githubDiscord, $githubArchive] as $connection) {
                    Delivery::factory()->for($event)->for($connection)->for($connection->destination)->create([
                        'status' => $index === 4 && $connection->is($githubArchive) ? 'failed' : 'delivered',
                        'attempts' => $index === 4 && $connection->is($githubArchive) ? 5 : 1,
                        'response_status' => $index === 4 && $connection->is($githubArchive) ? 503 : 204,
                        'last_attempted_at' => $referenceTime->subMinutes($index * 7 - 1),
                        'delivered_at' => $index === 4 && $connection->is($githubArchive) ? null : $referenceTime->subMinutes($index * 7 - 1),
                    ]);
                }
            }
        }
    }
}
