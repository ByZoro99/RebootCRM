<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Platform;
use App\Models\Profile;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = new SaleService();

        foreach (['Netflix' => 9.99, 'Disney+' => 7.99] as $name => $price) {
            $platform = Platform::firstOrCreate(['name' => $name], ['base_price' => $price, 'profiles_per_account' => 5]);
            $account = Account::factory()->create(['platform_id' => $platform->id, 'profiles_total' => 5]);
            Profile::factory()->count(5)->create([
                'account_id' => $account->id,
                'status' => ProfileStatus::Free->value,
            ]);
        }

        $customers = Customer::factory()->count(3)->create();

        $freeProfiles = Profile::where('status', ProfileStatus::Free->value)->take(2)->get();
        $service->create($customers[0], $freeProfiles[0], 5.0, 1, 'efectivo');
        $service->create($customers[1], $freeProfiles[1], 5.0, 1, 'transferencia');
    }
}
