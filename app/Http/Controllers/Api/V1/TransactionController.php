<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\Pos\Transaction;
use App\Models\Pos\TransactionDetail;
use App\Traits\WithGenerateReference;
use App\Traits\WithGetFilterDataApi;

class TransactionController extends Controller
{
    use WithGetFilterDataApi, WithGenerateReference;

    // Get all data
    public function index(Request $request) {
        $data = $this->getDataWithFilter(
            model: Transaction::with('user'),
            searchBy: [
                'reference',
                'grand_total',
                'created_at',
            ],
            orderBy: $request?->orderBy ?? 'created_at',
            order: $request?->order ?? 'desc',
            paginate: $request?->paginate ?? 10,
            searchBySpecific: $request?->searchBySpecific ?? '',
            s: $request?->search ?? '',
        );

        return $this->responseWithSuccess($data);
    }

    // Get detail data
    public function show($id) {
        $data = Transaction::with('user', 'details', 'details.product', 'details.productVariant')->find($id);

        // If the data is not found
        if(!$data) return $this->responseNotFound();

        return $this->responseWithSuccess($data);
    }

    // Create new transaction
    public function create(Request $request) {
        $data = $request->validate([
            'data' => 'required|array',
            'data.*.product_id' => 'required_if:data.*.product_variant_id,null|exists:products,id',
            'data.*.product_variant_id' => 'required_if:data.*.product_id,null|exists:product_variants,id',
            'data.*.quantity' => 'required|integer|min:1',
        ]);
        $response = null;

        // DB transaction
        DB::beginTransaction();

        try {
            // Create new transaction
            $reference = $this->generateReference(
                model: new Transaction,
                prefix: 'TRX',
            );
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'reference' => $reference['code'],
                'ref_number' => $reference['number'],
                'total' => 0,
                'grand_total' => 0,
            ]);

            // Total
            $total = 0;

            foreach($data['data'] as $item) {
                $product = null;

                if (isset($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                } else {
                    $product = ProductVariant::find($item['product_variant_id']);
                }

                // If Stock is Insufficient
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return $this->responseWithError('Insufficient stock for product: ' . $product->name, 422);
                }

                // Create transaction detail
                $detailTotal = $product->price * $item['quantity'];
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => isset($item['product_id']) ? $product->id : null,
                    'product_variant_id' => isset($item['product_variant_id']) ? $product->id : null,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $detailTotal,
                    'grand_total' => $detailTotal,
                ]);

                // Update product stock
                $product->decrement('stock', $item['quantity']);

                // Update total
                $total += $detailTotal;
            }

            $transaction->total = $total;
            $transaction->grand_total = $total;
            $transaction->save();

            $response = $transaction->refresh();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseWithError('Failed to create transaction: ' . $e->getMessage(), 500);
        }

        return $this->responseWithSuccess($response);
    }
}
