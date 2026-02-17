<?php
namespace Database\Factories;

use App\Models\DocumentItem;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentItemFactory extends Factory
{
    protected $model = DocumentItem::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $price = $this->faker->randomFloat(2, 10, 1000);
        $amount = $quantity * $price;

        return [
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 500),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'undiscounted_amount' => $this->faker->randomFloat(2, 50, 5200),
            'quantity' => $this->faker->numberBetween(1, 100),
            'discount' => $this->faker->randomFloat(2, 0, 50),
            'production_note_id' => FactoryHelpers::getRandomId(\App\Models\ProductionNote::class),
            'output_note_id' => FactoryHelpers::getRandomId(\App\Models\OutputNote::class),
            'return_note_id' => FactoryHelpers::getRandomId(\App\Models\ReturnNote::class),
            'invoice_id' => FactoryHelpers::getRandomId(\App\Models\Invoice::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),
            'delivery_note_id' => FactoryHelpers::getRandomId(\App\Models\DeliveryNote::class),
            'order_note_id' => FactoryHelpers::getRandomId(\App\Models\OrderNote::class),
            'order_receipt_id' => FactoryHelpers::getRandomId(\App\Models\OrderReceipt::class),
            'invoice_credit_id' => FactoryHelpers::getRandomId(\App\Models\InvoiceCredit::class),

        ];
    }
}
