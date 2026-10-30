<?php

declare(strict_types=1);

/**
 * Full reference example for levilabs/laravel-wayl.
 *
 * This is illustrative, not part of the package's autoload — copy what you
 * need into your own app. It assumes an App\Models\Order with an integer
 * `total` in whole IQD (minimum 1000) and a string `reference` you can
 * send to Wayl as referenceId. referenceId must stay unique for every
 * link you ever create on that merchant.
 *
 * Covers every public method: verify the API key, create a link, the
 * browser return URL, the HMAC-verified webhook, explicit status polling,
 * invalidation, and refunds.
 */

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Data\Links\WebhookPayload;
use LeviLabs\LaravelWayl\Enums\LineItemType;
use LeviLabs\LaravelWayl\Facades\WaylLink;
use LeviLabs\LaravelWayl\Facades\WaylRefund;
use LeviLabs\LaravelWayl\Models\WaylLink as WaylLinkModel;

class PaymentController extends Controller
{
    /**
     * Confirm the merchant token before you store it. WaylLink::verify()
     * calls GET /api/v1/verify-auth-key and throws WaylException when the
     * key is rejected.
     */
    public function verifyCredentials()
    {
        return response()->json(['message' => WaylLink::verify()]);
    }

    /**
     * Create a Wayl checkout link and send the buyer to it.
     */
    public function pay(Order $order)
    {
        $link = WaylLink::create(
            referenceId: $order->reference,
            total: (int) $order->total,
            lineItems: [
                new LineItem('Basket', (int) $order->total, LineItemType::Increase),
            ],
            customParameter: (string) $order->id,
        );

        $order->waylLink()->associate(
            WaylLinkModel::query()->where('reference_id', $link->referenceId)->first()
        )->save();

        return redirect($link->url);
    }

    /**
     * Wayl POSTs here when a payment updates. The signature covers the raw
     * body bytes, and the request arrives as Content-Type: text/plain, so
     * $request->all() can be empty. Read getContent() and verify that
     * string before decoding it.
     *
     * The header is x-wayl-signature-256 (bare hex HMAC-SHA256). Wayl's own
     * plugins also send x-wayl-signature, so accept either.
     *
     * There is no timestamp in the signature. Deduplicate on the payload
     * id, then confirm the link with WaylLink::get() before fulfilling.
     * Webhook paymentStatus "Paid" is not a link status — Complete and
     * Delivered are.
     *
     * Return HTTP 200 once you have recorded a genuine delivery so Wayl
     * stops retrying. Reject a bad signature with 400.
     */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        $signature = $request->header('x-wayl-signature-256', $request->header('x-wayl-signature'));

        if (! is_string($signature) || ! WaylLink::verifyWebhookSignature($raw, $signature)) {
            report(new \RuntimeException('Wayl webhook signature verification failed.'));

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $payload = WebhookPayload::fromRawBody($raw);

        if ($payload->id !== null && ! Cache::add('wayl:webhook:'.$payload->id, true, now()->addDays(7))) {
            return response()->noContent();
        }

        if ($payload->referenceId === null) {
            return response()->noContent();
        }

        $link = WaylLink::get($payload->referenceId);

        if ($link->isPaid()) {
            Order::query()->where('reference', $link->referenceId)->update(['status' => 'paid']);
        }

        return response()->noContent();
    }

    /**
     * The page the buyer lands on after redirectionUrl. Wayl appends
     * referenceId and orderid. The webhook may not have arrived yet, so
     * load the link here as well. orderid is Wayl's link id.
     */
    public function returned(Request $request)
    {
        $referenceId = $request->string('referenceId')->toString();
        $link = WaylLink::get($referenceId);

        $order = Order::query()->where('reference', $referenceId)->firstOrFail();

        if ($link->isPaid()) {
            $order->update(['status' => 'paid']);

            return redirect()->route('orders.show', $order)->with('status', 'Payment successful.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment was not completed.');
    }

    /**
     * Cancel an unpaid link. invalidateIfPending() is the safer call when
     * the buyer might already have started paying — Wayl leaves a link
     * alone once it is no longer pending.
     */
    public function invalidate(Order $order)
    {
        $link = WaylLink::invalidateIfPending($order->reference);

        if ($link->status->isTerminal() && ! $link->isPaid()) {
            $order->update(['status' => 'cancelled']);
        }

        return response()->json([
            'reference_id' => $link->referenceId,
            'status' => $link->status->value,
        ]);
    }

    /**
     * Refund a paid order. The reason must be 100–1500 characters and the
     * amount is whole IQD, at least 1000. This moves real money — put your
     * own approval step in front of it. Omit refundMethod and accountNumber
     * to refund the original payment method; pass both to send it to
     * QiCard, FIB, or ZainCash instead.
     */
    public function refund(Order $order, Request $request)
    {
        $refund = WaylRefund::create(
            referenceId: $order->reference,
            reason: $request->string('reason')->toString(),
            amount: $request->integer('amount'),
        );

        if ($refund->isSuccessful()) {
            $order->update(['status' => 'refunded']);
        }

        return response()->json([
            'refund_id' => $refund->id,
            'status' => $refund->status->value,
        ]);
    }
}
