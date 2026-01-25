<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 訂單資料 Seeder
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::where('status', 'ACTIVE')->get();
        $customers = Customer::where('status', 'ACTIVE')->get();
        $products = Product::where('status', 'ACTIVE')->get();
        $cashier = User::first(); // 使用第一個使用者作為收銀員

        // 建立過去30天的訂單
        for ($day = 30; $day >= 0; $day--) {
            $orderDate = now()->subDays($day);

            // 每天 3-8 筆訂單
            $ordersPerDay = mt_rand(3, 8);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $store = $stores->random();
                $customer = mt_rand(1, 100) > 30 ? $customers->random() : null; // 70% 有會員

                // 產生訂單編號
                $orderNo = sprintf(
                    'ORD%s%s%04d',
                    $orderDate->format('Ymd'),
                    $store->code,
                    mt_rand(1, 9999)
                );

                // 隨機選擇 1-5 個商品
                $orderProducts = $products->random(mt_rand(1, 5));

                $subtotal = 0;
                $items = [];

                foreach ($orderProducts as $product) {
                    $quantity = mt_rand(1, 3);
                    $unitPrice = $customer ? $product->member_price ?? $product->selling_price : $product->selling_price;
                    $itemSubtotal = $unitPrice * $quantity;

                    $items[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'original_price' => $product->selling_price,
                        'discount_amount' => ($product->selling_price - $unitPrice) * $quantity,
                        'tax_amount' => round($itemSubtotal * 0.05, 2),
                        'subtotal' => $itemSubtotal,
                        'cost_price' => $product->cost_price,
                    ];

                    $subtotal += $itemSubtotal;
                }

                // 計算折扣（會員等級折扣，discount_rate 是百分比如 5, 10, 15, 20）
                $discountPercent = $customer && $customer->level ? $customer->level->discount_rate : 0;
                $discountAmount = round($subtotal * ($discountPercent / 100), 2);

                // 稅額（5%）
                $taxAmount = round(($subtotal - $discountAmount) * 0.05, 2);

                // 點數使用（10% 機率使用點數）
                $pointsUsed = 0;
                $pointsAmount = 0;
                if ($customer && $customer->available_points > 100 && mt_rand(1, 100) <= 10) {
                    $pointsUsed = min(mt_rand(100, 500), $customer->available_points);
                    $pointsAmount = $pointsUsed; // 1點=1元
                }

                $totalAmount = $subtotal - $discountAmount + $taxAmount - $pointsAmount;

                // 點數獲得（消費金額的1%）
                $pointsMultiplier = $customer && $customer->level ? $customer->level->points_multiplier : 1.0;
                $pointsEarned = (int) ($totalAmount * 0.01 * $pointsMultiplier);

                // 決定訂單狀態
                $status = $this->getOrderStatus($day);

                $order = Order::create([
                    'order_no' => $orderNo,
                    'store_id' => $store->id,
                    'cashier_id' => $cashier?->id,
                    'customer_id' => $customer?->id,
                    'order_date' => $orderDate->setTime(mt_rand(10, 21), mt_rand(0, 59), 0),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'points_earned' => $status === 'COMPLETED' ? $pointsEarned : 0,
                    'points_used' => $pointsUsed,
                    'points_amount' => $pointsAmount,
                    'status' => $status,
                ]);

                // 建立訂單明細
                foreach ($items as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }

                // 建立付款紀錄（已完成的訂單）
                if ($status === 'COMPLETED') {
                    $this->createPayment($order, $totalAmount);
                }
            }
        }

        // 建立一些作廢的訂單示例
        $this->createVoidedOrders($stores, $customers, $products, $cashier);
    }

    /**
     * 根據天數決定訂單狀態
     * 可用狀態: COMPLETED, VOIDED, REFUNDED, PARTIAL_REFUND
     */
    private function getOrderStatus(int $daysAgo): string
    {
        if ($daysAgo <= 3) {
            // 近3天可能有作廢或退貨的
            $rand = mt_rand(1, 100);
            if ($rand <= 5) {
                return 'VOIDED';
            }
            if ($rand <= 10) {
                return 'REFUNDED';
            }
        }

        return 'COMPLETED';
    }

    /**
     * 建立付款紀錄
     */
    private function createPayment(Order $order, float $totalAmount): void
    {
        $paymentMethods = ['CASH', 'CREDIT_CARD', 'LINE_PAY', 'EASY_CARD'];
        $method = $paymentMethods[array_rand($paymentMethods)];

        $payment = [
            'order_id' => $order->id,
            'payment_method' => $method,
            'amount' => $totalAmount,
            'status' => 'SUCCESS',
        ];

        if ($method === 'CASH') {
            // 現金付款
            $receivedAmount = ceil($totalAmount / 100) * 100; // 進位到百元
            $payment['received_amount'] = $receivedAmount;
            $payment['change_amount'] = $receivedAmount - $totalAmount;
        } elseif ($method === 'CREDIT_CARD') {
            // 信用卡付款
            $payment['received_amount'] = $totalAmount;
            $payment['change_amount'] = 0;
            $payment['card_last_four'] = sprintf('%04d', mt_rand(0, 9999));
            $payment['auth_code'] = sprintf('%06d', mt_rand(0, 999999));
        } else {
            // 電子支付
            $payment['received_amount'] = $totalAmount;
            $payment['change_amount'] = 0;
            $payment['reference_no'] = strtoupper(bin2hex(random_bytes(8)));
        }

        Payment::create($payment);
    }

    /**
     * 建立作廢訂單範例
     */
    private function createVoidedOrders($stores, $customers, $products, $cashier): void
    {
        // 建立幾筆作廢訂單
        for ($i = 0; $i < 3; $i++) {
            $store = $stores->random();
            $customer = $customers->random();
            $orderProducts = $products->random(mt_rand(1, 3));

            $orderNo = sprintf('ORD%s%s%04d', now()->subDays(mt_rand(1, 7))->format('Ymd'), $store->code, mt_rand(1, 9999));

            $subtotal = 0;
            $items = [];

            foreach ($orderProducts as $product) {
                $quantity = mt_rand(1, 2);
                $unitPrice = $product->member_price ?? $product->selling_price;
                $itemSubtotal = $unitPrice * $quantity;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $product->selling_price,
                    'discount_amount' => 0,
                    'tax_amount' => round($itemSubtotal * 0.05, 2),
                    'subtotal' => $itemSubtotal,
                    'cost_price' => $product->cost_price,
                ];

                $subtotal += $itemSubtotal;
            }

            $taxAmount = round($subtotal * 0.05, 2);
            $totalAmount = $subtotal + $taxAmount;

            $order = Order::create([
                'order_no' => $orderNo,
                'store_id' => $store->id,
                'cashier_id' => $cashier?->id,
                'customer_id' => $customer->id,
                'order_date' => now()->subDays(mt_rand(1, 7)),
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'points_earned' => 0,
                'points_used' => 0,
                'points_amount' => 0,
                'status' => 'VOIDED',
                'notes' => '客戶取消訂單',
            ]);

            foreach ($items as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }
        }
    }
}
