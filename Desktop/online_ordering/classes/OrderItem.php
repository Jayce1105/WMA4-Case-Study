<?php

class OrderItem
{
    private int $productId;
    private string $productName;
    private int $quantity;
    private float $unitPrice;

    public function __construct(int $productId, string $productName, int $quantity, float $unitPrice)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getSubtotal(): float
    {
        return $this->quantity * $this->unitPrice;
    }
}
