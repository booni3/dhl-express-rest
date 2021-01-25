<?php


namespace Booni3\DhlExpressRest\Response;


use Carbon\Carbon;

class RatesResponse
{
    public $products = [];
    public $exchangeRates = [];

    public static function fromArray(array $data)
    {
        $static = new static();
        $static->products = $data['products'] ?? [];
        $static->exchangeRates = $data['exchangeRates'] ?? [];
        return $static;
    }

    public function p()
    {
        return array_map(function ($row) {
            return [
                'productName' => $row['productName'],
                'productCode' => $row['productCode'],
                'networkTypeCode' => $row['networkTypeCode'],
                'totalPrice' => $this->billingPrice($row['totalPrice'])['price'] ?? 0,
                'priceCurrency' => $this->billingPrice($row['totalPrice'])['priceCurrency'] ?? '',
                'estimatedDeliveryDateAndTime' => Carbon::make($row['deliveryCapabilities']['estimatedDeliveryDateAndTime'] ?? null),
                'totalTransitDays' => $row['deliveryCapabilities']['totalTransitDays'] ?? null
            ];
        }, $this->products);
    }

    protected function billingPrice(array $array): array
    {
        return array_values(
                array_filter($array, function ($tp) {
                    return $tp['currencyType'] == 'BILLC'; // BILLING  CURRENCY
                })
            )[0] ?? [];
    }

    /**
     * Return products sorted by cheapest
     *
     * @return array[]
     */
    public function productsSortedByCheapest()
    {
        $products = $this->p();
        usort($products, [$this, 'sortByCheapest']);
        return $products;
    }

    /**
     * Return products sorted by cheapest
     *
     * @return array
     */
    public function cheapestProduct()
    {
        return $this->productsSortedByCheapest()[0] ?? null;
    }

    protected function sortByCheapest($a, $b)
    {
        return $a['totalPrice'] - $b['totalPrice'];
    }

    /**
     * Return products sorted by fastest
     *
     * @return array[]
     */
    public function productsSortedByFastest()
    {
        $products = $this->p();
        usort($products, [$this, 'sortByFastest']);
        return $products;
    }

    /**
     * Get the fastest delivery, ignoring cost.
     *
     * @return array
     */
    public function fastestProduct()
    {
        return $this->productsSortedByFastest()[0]??null;
    }

    protected function sortByFastest($a, $b)
    {
        return $a['estimatedDeliveryDateAndTime']->greaterThan($b['estimatedDeliveryDateAndTime']);
    }

    /**
     * Shortest days transit, ignoring time of day.
     * Then sort by cheapest.
     *
     * @return array
     */
    public function shortestTransitDaysAndCheapest()
    {
        $products = $this->p();
        usort($products, [$this, 'sortByTransitDaysThenCheapest']);
        return $products[0];
    }

    public function sortByTransitDaysThenCheapest($a, $b)
    {
        if($a['totalTransitDays'] == $b['totalTransitDays']){
            return $a['totalPrice'] - $b['totalPrice'];
        }

        return $a['totalTransitDays'] - $b['totalTransitDays'];
    }

}
