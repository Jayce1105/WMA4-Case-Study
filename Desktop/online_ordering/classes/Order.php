<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../interfaces/CRUDInterface.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/OrderItem.php';
require_once __DIR__ . '/../exceptions/InsufficientStockException.php';

class Order extends Model implements CRUDInterface
{
    private ?int $orderId = null;
    private int $customerId = 0;
    private string $deliveryAddress = '';
    private string $status = 'pending';

    /** @var OrderItem[] */
    private array $items = [];

    public function getId(): ?int
    {
        return $this->orderId;
    }

    public function setCustomerId(int $id): void
    {
        $this->customerId = $id;
    }

    public function setDeliveryAddress(string $address): void
    {
        $this->deliveryAddress = trim($address);
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }

        return $total;
    }

    public function validate(): bool
    {
        return $this->customerId > 0 && $this->deliveryAddress !== '' && count($this->items) > 0;
    }

    public function placeOrder(): int
    {
        if (!$this->validate()) {
            throw new InvalidArgumentException('Order is missing required information.');
        }

        $this->db->beginTransaction();

        try {
            foreach ($this->items as $item) {
                $product = new Product($item->getProductId());
                $product->reduceStock($item->getQuantity());
            }

            $stmt = $this->db->prepare(
                'INSERT INTO orders (customer_id, status, delivery_address, total_amount)
                 VALUES (:cust, :status, :addr, :total)'
            );
            $stmt->execute([
                'cust' => $this->customerId,
                'status' => $this->status,
                'addr' => $this->deliveryAddress,
                'total' => $this->getTotal(),
            ]);
            $this->orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (:order, :product, :qty, :price)'
            );
            foreach ($this->items as $item) {
                $itemStmt->execute([
                    'order' => $this->orderId,
                    'product' => $item->getProductId(),
                    'qty' => $item->getQuantity(),
                    'price' => $item->getUnitPrice(),
                ]);
            }

            $this->db->commit();

            return $this->orderId;
        } catch (InsufficientStockException $e) {
            $this->db->rollBack();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function create(): bool
    {
        return $this->placeOrder() > 0;
    }

    public function read(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE order_id = :id');

        return $stmt->execute(['status' => $data['status'], 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orders WHERE order_id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public static function findAll(
        PDO $db,
        string $status = '',
        string $search = '',
        string $sort = 'date_desc',
        int $limit = 20,
        int $offset = 0
    ): array {
        $sql = 'SELECT o.*, c.full_name, c.email
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE 1=1';
        $params = [];

        if ($status !== '') {
            $sql .= ' AND o.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (c.full_name LIKE :search OR o.order_id = :orderId)';
            $params['search'] = '%' . $search . '%';
            $params['orderId'] = is_numeric($search) ? (int) $search : 0;
        }

        $sortMap = [
            'date_desc' => 'o.order_date DESC',
            'date_asc' => 'o.order_date ASC',
            'total_desc' => 'o.total_amount DESC',
            'total_asc' => 'o.total_amount ASC',
        ];
        $sql .= ' ORDER BY ' . ($sortMap[$sort] ?? $sortMap['date_desc']);
        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countAll(PDO $db, string $status = '', string $search = ''): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE 1=1';
        $params = [];

        if ($status !== '') {
            $sql .= ' AND o.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (c.full_name LIKE :search OR o.order_id = :orderId)';
            $params['search'] = '%' . $search . '%';
            $params['orderId'] = is_numeric($search) ? (int) $search : 0;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }
}
