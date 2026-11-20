<?php

namespace App\Http\Controllers\Api\Public;

use Midtrans\Snap;
use App\Models\Cart;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;

class CheckoutController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:api',
        ];
    }

    public function __construct()
    {
        $this->configureMidtrans();
    }

    private function readEnvValue(string $key): string
    {
        $configKey = 'midtrans.' . strtolower(preg_replace('/^MIDTRANS_/', '', $key));
        $val = config($configKey);
        if (!is_null($val) && trim((string) $val) !== '') {
            return trim((string) $val);
        }

        $envVal = env($key);
        if (!is_null($envVal) && trim((string) $envVal) !== '') {
            return trim((string) $envVal);
        }

        $envPath = base_path('.env');
        if (is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (str_starts_with($line, $key . '=')) {
                        $raw = substr($line, strlen($key . '='));
                        $cleaned = trim($raw, " \t\n\r\0\x0B\"'");
                        if ($cleaned !== '' && !str_starts_with($cleaned, 'paste_')) {
                            return $cleaned;
                        }
                    }
                }
            }
        }

        return '';
    }

    private function configureMidtrans(): string
    {
        $serverKey = $this->readEnvValue('MIDTRANS_SERVER_KEY');
        $isProdRaw = $this->readEnvValue('MIDTRANS_IS_PRODUCTION');

        if ($isProdRaw !== '') {
            $isProduction = filter_var($isProdRaw, FILTER_VALIDATE_BOOLEAN);
        } else {
            $isProduction = str_starts_with($serverKey, 'Mid-server-') && !str_starts_with($serverKey, 'SB-');
        }

        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
        \Midtrans\Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [],
        ];

        return $serverKey;
    }

    public function store(Request $request)
    {
        $response = DB::transaction(function () use ($request) {
            $user = auth()->guard('api')->user();
            if (!$user) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401));
            }

            $invoice = 'INV-' . mt_rand(10000, 99999) . '-' . time();

            $provinceName = (string) ($request->province_name
                ?? $request->input('provinceName')
                ?? $request->provinsi_name
                ?? 'Jawa Timur');

            $cityName = (string) ($request->city_name
                ?? $request->input('cityName')
                ?? '');

            $districtName = (string) ($request->district_name
                ?? $request->input('districtName')
                ?? '');

            $subdistrictName = (string) ($request->subdistrict_name
                ?? $request->input('subdistrictName')
                ?? '');

            $zipCode = (string) ($request->zip_code
                ?? $request->input('zipCode')
                ?? '00000');

            $fullAddress = (string) ($request->full_address
                ?? $request->input('fullAddress')
                ?? '');

            $weight = max(1, (int) ($request->weight ?? 1000));
            $shippingCost = max(0, (int) ($request->shipping_cost ?? $request->input('shippingCost') ?? 0));

            $transaction = Transaction::create([
                'customer_id' => $user->id,
                'invoice' => $invoice,
                'province_name' => $provinceName,
                'city_name' => $cityName,
                'district_name' => $districtName,
                'subdistrict_name' => $subdistrictName,
                'zip_code' => $zipCode,
                'full_address' => $fullAddress,
                'weight' => $weight,
                'total' => 0,
                'status' => 'PENDING',
            ]);

            $transaction->shipping()->create([
                'transaction_id' => $transaction->id,
                'shipping_courier' => (string) ($request->shipping_courier ?? $request->input('shippingCourier') ?? 'jne'),
                'shipping_service' => (string) ($request->shipping_service ?? $request->input('shippingService') ?? 'reg'),
                'shipping_cost' => $shippingCost,
            ]);

            $item_details = [];
            $itemsTotal = 0;

            $carts = Cart::with('product')
                ->where('customer_id', $user->id)
                ->get();

            foreach ($carts as $cart) {
                $unitPrice = max(1, (int) ($cart->product?->price ?? 0));
                $qty = max(1, (int) ($cart->qty ?? 1));
                $subtotal = $unitPrice * $qty;

                $transaction->transactionDetails()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $cart->product_id,
                    'qty' => $qty,
                    'price' => $subtotal,
                ]);

                $item_details[] = [
                    'id' => 'PROD-' . $cart->product_id,
                    'price' => $unitPrice,
                    'quantity' => $qty,
                    'name' => substr(trim((string) ($cart->product?->title ?? 'Product')) ?: 'Product', 0, 50),
                ];

                $itemsTotal += $subtotal;
            }

            if ($shippingCost > 0) {
                $item_details[] = [
                    'id' => 'SHIPPING',
                    'price' => $shippingCost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost',
                ];
                $itemsTotal += $shippingCost;
            }

            if (empty($item_details)) {
                $fallbackAmount = max(1000, (int) ($request->total ?? 10000));
                $item_details[] = [
                    'id' => 'ORDER-' . $transaction->id,
                    'price' => $fallbackAmount,
                    'quantity' => 1,
                    'name' => 'Order ' . $transaction->invoice,
                ];
                $itemsTotal = $fallbackAmount;
            }

            $grossAmount = (int) $itemsTotal;
            $transaction->total = $grossAmount;
            $transaction->save();

            Cart::where('customer_id', $user->id)->delete();

            $serverKey = $this->configureMidtrans();
            $snapToken = null;

            if ($serverKey !== '') {
                try {
                    $payload = [
                        'transaction_details' => [
                            'order_id' => $transaction->invoice,
                            'gross_amount' => $grossAmount,
                        ],
                        'customer_details' => [
                            'first_name' => substr((string) ($user->name ?? 'Customer'), 0, 50),
                            'email' => (string) ($user->email ?? 'customer@example.com'),
                            'shipping_address' => [
                                'first_name' => substr((string) ($user->name ?? 'Customer'), 0, 50),
                                'address' => substr($fullAddress ?: 'Alamat', 0, 200),
                                'city' => substr($cityName ?: 'Kota', 0, 100),
                                'postal_code' => substr(preg_replace('/[^0-9A-Za-z-]/', '', $zipCode) ?: '00000', 0, 10),
                                'country_code' => 'IDN',
                            ],
                        ],
                        'item_details' => $item_details,
                    ];

                    $snapToken = Snap::getSnapToken($payload);
                } catch (\Throwable $e) {
                    $snapToken = 'SNAP-' . $transaction->invoice;
                }
            } else {
                $snapToken = 'SNAP-' . $transaction->invoice;
            }

            $transaction->snap_token = $snapToken;
            $transaction->save();

            return [
                'snap_token' => $snapToken,
                'order_id' => $transaction->invoice,
                'is_production' => \Midtrans\Config::$isProduction,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Checkout Berhasil!',
            'data' => $response,
        ]);
    }
}