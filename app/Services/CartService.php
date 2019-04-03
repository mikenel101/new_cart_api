<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Exception\RequestException;
use MikesLumenBase\Utils\TaxUtils;
use MikesLumenApi\Exceptions\AppException;
use App\Models\Product;
use App\Models\Shipment;

class CartService
{
    /**
     * Validate cart items
     *
     * @param  string $shopId
     * @param  array  $cartItems
     * @param  array  $headers
     *
     * @throws \MikesLumenApi\Exceptions\ValidatorException
     *
     * @return void
     */
    public function validateCartItemsOrFail(string $shopId, array $cartItems, array$headers)
    {
        $productVariantStockQueries = array_map(function (array $cartItem) use ($shopId) {
            return [
                'product_variant_id' => $cartItem['product_variant_id'],
                'shop_id' => $shopId,
            ];
        }, $cartItems);

        $productVariantStocks = app('fetcher')->get(
            getenv('API_INVENTORY_ENDPOINT'),
            'product-variant-stocks',
            $productVariantStockQueries,
            $headers
        );

        $productVariantStockByProductVariantId = collect($productVariantStocks)->mapWithKeys(function ($productVariantStock) {
            return [$productVariantStock['product_variant_id'] => $productVariantStock];
        });

        $errors = [];
        foreach ($cartItems as $index => $cartItem) {
            if ($cartItem['stock_mode'] == Product::STOCK_MODE_UNUSED) {
                continue;
            }

            $productVariantId = $cartItem['product_variant_id'];
            if (!isset($productVariantStockByProductVariantId[$productVariantId])) {
                $errors["cart_items.$index"] = trans('message.out_of_stock');
                continue;
            }

            $productVariantStock = $productVariantStockByProductVariantId[$productVariantId];
            if ((int)$cartItem['quantity'] > (int)$productVariantStock['quantity']) {
                $errors["cart_items.$index"] = trans('message.out_of_stock');
                continue;
            }
        }

        if (!empty($errors)) {
            throw new \MikesLumenApi\Exceptions\ValidatorException($errors);
        }
    }

    /**
     * Validate Payment
     *
     * @param  array  $payments
     * @param  array  $headers
     *
     * @throws \MikesLumenApi\Exceptions\ValidatorException
     *
     * @return void
     */
    public function validatePaymentsOrFail(array $payments, array $headers)
    {
        if (empty($payments)) {
            throw new \MikesLumenApi\Exceptions\ValidatorException(['payments' => trans('message.invalid_payment')]);
        }

        foreach ($payments as $payment) {
            // If validation failed, ValidatorException will be thrown.
            app('fetcher')->post(
                getenv('API_PAYMENT_ENDPOINT'),
                "payments/validate",
                $payment,
                $headers
            );
        }
    }


    /**
     * Validate shipments
     *
     * @param  array  $shipments
     * @param  array  $headers
     *
     * @throws \MikesLumenApi\Exceptions\ValidatorException
     *
     * @return void
     */
    public function validateShipmentsOrFail(array $shipments, array $headers)
    {
        if (empty($shipments)) {
            throw new \MikesLumenApi\Exceptions\ValidatorException(['payments' => trans('message.invalid_shipment')]);
        }

        foreach ($shipments as $shipment) {
            // If validation failed, ValidatorException will be thrown.
            app('fetcher')->post(
                getenv('API_SHIPPING_ENDPOINT'),
                "shipments/validate",
                $shipment,
                $headers
            );
        }
    }


    /**
     * Build order from input
     *
     * @param array $inputOrder
     * @param array $headers
     *
     * @return array
     */
    public function buildOrder(array $inputOrder, array $headers)
    {
        $order = array_merge($inputOrder, [
            'shop_mode' => 1,
            'total_paid_taxless' => 0,
            'total_paid_tax' => 0,
            'total_items_taxless' => 0,
            'total_items_tax' => 0,
            'total_payments_taxless' => 0,
            'total_payments_tax' => 0,
            'total_shippings_taxless' => 0,
            'total_shippings_tax' => 0
        ]);

        $order['order_items'] = $this->buildOrderItems($inputOrder['order_items'], $headers);
        foreach ($order['order_items'] as $orderItem) {
            $order['total_items_taxless'] += $orderItem['total_price_taxless'];
            $order['total_items_tax'] += $orderItem['total_price_tax'];
        }

        $order['payments'] = $this->buildPayments($inputOrder['payments'], $headers);
        foreach ($order['payments'] as $payment) {
            $order['total_payments_taxless'] += $payment['charge_taxless'];
            $order['total_payments_tax'] += $payment['charge_tax'];
        }

        $order['shipments'] = $this->buildShipments($inputOrder['shipments'], $inputOrder['shop_id'], $headers);
        foreach ($order['shipments'] as $shipment) {
            $order['total_shippings_taxless'] += $shipment['cost_taxless'];
            $order['total_shippings_tax'] += $shipment['cost_tax'];
        }

        $order['total_paid_taxless'] = $order['total_items_taxless'] +  $order['total_payments_taxless'] + $order['total_shippings_taxless'];
        $order['total_paid_tax'] = $order['total_items_tax'] +  $order['total_payments_tax'] + $order['total_shippings_tax'];

        if (count($order['payments']) === 1) {
            $order['payments'][0]['amount'] =  $order['total_paid_taxless'] + $order['total_paid_tax'];
        } else {
            throw new \MikesLumenApi\Exceptions\NotImplementedException();
        }

        return $order;
    }

    /**
     * Build order items from input
     *
     * @param array $inputOrderItems
     * @param array $headers
     *
     * @return array
     */
    protected function buildOrderItems(array $inputOrderItems, array $headers)
    {
        $orderItems = [];
        foreach ($inputOrderItems as $inputOrderItem) {
            $orderItem = $inputOrderItem;
            $orderItem['sales_price_taxless'] = TaxUtils::computePriceWithoutTax(
                $inputOrderItem['unit_price'],
                $inputOrderItem['tax_rate'],
                $inputOrderItem['tax_rule']
            );
            $orderItem['sales_price_tax'] = TaxUtils::calculateTax(
                $inputOrderItem['unit_price'],
                $inputOrderItem['tax_rate'],
                $inputOrderItem['tax_rule']
            );
            $orderItem['total_price_taxless'] = $orderItem['sales_price_taxless'] * $inputOrderItem['quantity'];
            $orderItem['total_price_tax'] = $orderItem['sales_price_tax'] * $inputOrderItem['quantity'];

            $orderItems[] = $orderItem;
        }
        return $orderItems;
    }

    /**
     * Build payments from input
     *
     * @param array $inputPayments
     * @param array $headers
     *
     * @return array
     */
    protected function buildPayments(array $inputPayments, array $headers)
    {
        if (empty($inputPayments)) {
            return [];
        }

        $payments = [];
        foreach ($inputPayments as $inputPayment) {
            $payment = $inputPayment;

            $paymentCharge = $this->getPaymentCharge($payment, $headers);
            if ($paymentCharge) {
                $payment['charge_taxless'] = $paymentCharge['charge_taxless'] ?? 0;
                $payment['charge_tax'] = $paymentCharge['charge_tax'] ?? 0;
            }

            $payments[] = $payment;
        }

        return $payments;
    }

    /**
     * Get payment's cost
     *
     * @return array
     */
    protected function getPaymentCharge(array $payment, array $headers)
    {
        $paymentCharge = app('fetcher')->post(
            getenv('API_PAYMENT_ENDPOINT'),
            "payment-methods/{$payment['payment_method_id']}/calculate-payment-cost",
            $payment,
            $headers
        );
        return $paymentCharge;
    }

    /**
     * Build Shipments from input
     *
     * @param array $inputShipments
     * @param string $shopId
     * @param array $headers
     *
     * @return array
     */
    protected function buildShipments(array $inputShipments, string $shopId, array $headers)
    {
        if (empty($inputShipments)) {
            return [];
        }

        $shopTaxRate = $this->getShopTaxRate($shopId, $headers);

        $shipments = [];
        foreach ($inputShipments as $inputShipment) {
            $shipment = $inputShipment;
            $shipment['cost_taxless'] = 0;
            $shipment['cost_tax'] = 0;
            $shipment['status'] = Shipment::STATUS_ORDER;

            foreach ($shipment['shipment_addresses'] as $shipmentAddress) {
                $shipmentAddress['tax_rate'] = $shopTaxRate;

                $shippingCost = $this->getShippingCost($shipment['shipping_method_id'], $shipmentAddress, $headers);
                if ($shippingCost) {
                    $shipment['cost_taxless'] += ($shippingCost['cost_taxless'] ?? 0);
                    $shipment['cost_tax'] += ($shippingCost['cost_tax'] ?? 0);
                }
            }

            $shipments[] = $shipment;
        }

        return $shipments;
    }

    /**
     * Get tax rate of a shop
     *
     * @param string $shopId
     * @param array $headers
     *
     * @return int
     */
    protected function getShopTaxRate(string $shopId, array $headers)
    {
        $shop = app('fetcher')->get(
            getenv('API_SHOP_ENDPOINT'),
            "shops/{$shopId}",
            [],
            $headers
        );

        $taxRate = 0;
        if (!empty($shop['shop_address'])) {
            $taxRate = $shop['shop_address']['tax_rate'] ?? 0;
        }

        return  $taxRate;
    }

    /**
     * Get cost of a shipping method for an address
     *
     * @param string $shippingMethodId
     * @param array $shipmentAddress
     *
     * @return array
     */
    protected function getShippingCost(string $shippingMethodId, array $shipmentAddress, array $headers)
    {
        $shippingCost = app('fetcher')->post(
            getenv('API_SHIPPING_ENDPOINT'),
            "shipping-methods/{$shippingMethodId}/calculate-shipping-cost",
            $shipmentAddress,
            $headers
        );
        return $shippingCost;
    }

    /**
     * Check out an order
     *
     * @param  array  $order
     * @param  array  $headers
     *
     * @return array
     */
    public function createOrder(array $order, array $headers)
    {
        $order = app('fetcher')->post(
            getenv('API_ORDER_ENDPOINT'),
            "orders",
            $order,
            $headers
        );

        return $order;
    }
}
