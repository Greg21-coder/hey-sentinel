<?php

namespace Database\Seeders;

use App\Enums\FeatureValueType;
use App\Enums\PlanStatus;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'free' => [
                'name' => 'Free',
                'description' => 'Get started with HeySentinel and explore competitor apps.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'sort_order' => 10,
                'features' => [
                    ['apps_tracked', '25', FeatureValueType::Integer],
                    ['saved_searches', '3', FeatureValueType::Integer],
                    ['alerts', 'false', FeatureValueType::Boolean],
                    ['export_csv', 'false', FeatureValueType::Boolean],
                    ['team_members', '1', FeatureValueType::Integer],
                ],
            ],
            'premium' => [
                'name' => 'Premium',
                'description' => 'For active product teams researching the Shopify App Store.',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'sort_order' => 20,
                'features' => [
                    ['apps_tracked', '', FeatureValueType::Unlimited],
                    ['saved_searches', '50', FeatureValueType::Integer],
                    ['alerts', 'true', FeatureValueType::Boolean],
                    ['export_csv', 'true', FeatureValueType::Boolean],
                    ['team_members', '5', FeatureValueType::Integer],
                    ['api_access', 'false', FeatureValueType::Boolean],
                ],
            ],
            'agency' => [
                'name' => 'Agency',
                'description' => 'For agencies serving multiple Shopify App developers.',
                'monthly_price' => 199,
                'yearly_price' => 1990,
                'sort_order' => 30,
                'features' => [
                    ['apps_tracked', '', FeatureValueType::Unlimited],
                    ['saved_searches', '', FeatureValueType::Unlimited],
                    ['alerts', 'true', FeatureValueType::Boolean],
                    ['export_csv', 'true', FeatureValueType::Boolean],
                    ['team_members', '', FeatureValueType::Unlimited],
                    ['api_access', 'true', FeatureValueType::Boolean],
                    ['white_label', 'true', FeatureValueType::Boolean],
                ],
            ],
        ];

        foreach ($catalog as $slug => $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'monthly_price' => $data['monthly_price'],
                    'yearly_price' => $data['yearly_price'],
                    'sort_order' => $data['sort_order'],
                    'status' => PlanStatus::Active->value,
                    'is_public' => true,
                ]
            );

            foreach ($data['features'] as [$key, $value, $type]) {
                $plan->features()->updateOrCreate(
                    ['feature_key' => $key],
                    [
                        'feature_value' => $value,
                        'value_type' => $type->value,
                    ]
                );
            }
        }
    }
}
