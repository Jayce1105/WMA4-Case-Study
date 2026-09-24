<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../interfaces/CRUDInterface.php';
require_once __DIR__ . '/../exceptions/InsufficientStockException.php';

class Product extends Model implements CRUDInterface
{
    private ?int $productId = null;
    private int $categoryId = 0;
    private string $name = '';
    private string $description = '';
    private float $price = 0.0;
    private int $stock = 0;
    private bool $isAvailable = true;

    public function __construct(?int $productId = null)
    {
        parent::__construct();
        if ($productId !== null) {
            $this->loadById($productId);
        }
    }

    public function getId(): ?int
    {
        return $this->productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = trim($description);
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): void
    {
        $this->isAvailable = $isAvailable;
    }

    public function validate(): bool
    {
        return $this->name !== '' && $this->price > 0 && $this->stock >= 0 && $this->categoryId > 0;
    }

    private function loadById(int $id): void
    {
        $row = $this->read($id);
        if ($row) {
            $this->productId = (int) $row['product_id'];
            $this->categoryId = (int) $row['category_id'];
            $this->name = $row['product_name'];
            $this->description = $row['description'] ?? '';
            $this->price = (float) $row['price'];
            $this->stock = (int) $row['stock_quantity'];
            $this->isAvailable = (bool) $row['is_available'];
        }
    }

    public function reduceStock(int $qty): void
    {
        // Re-read the stock with a row lock (must run inside an active transaction —
        // see Order::placeOrder()) so two orders placed at the same moment can't both
        // pass the check against the same stale stock figure.
        $lockStmt = $this->db->prepare('SELECT stock_quantity FROM products WHERE product_id = :id FOR UPDATE');
        $lockStmt->execute(['id' => $this->productId]);
        $row = $lockStmt->fetch();
        $this->stock = $row ? (int) $row['stock_quantity'] : 0;

        if ($qty > $this->stock) {
            throw new InsufficientStockException(
                "Not enough stock for \"{$this->name}\" (requested {$qty}, available {$this->stock})."
            );
        }

        $this->stock -= $qty;
        $stmt = $this->db->prepare('UPDATE products SET stock_quantity = :stock WHERE product_id = :id');
        $stmt->execute(['stock' => $this->stock, 'id' => $this->productId]);
    }

    public function create(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO products (category_id, product_name, description, price, stock_quantity, is_available)
             VALUES (:cat, :name, :desc, :price, :stock, :avail)'
        );
        $ok = $stmt->execute([
            'cat' => $this->categoryId,
            'name' => $this->name,
            'desc' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'avail' => $this->isAvailable ? 1 : 0,
        ]);

        if ($ok) {
            $this->productId = (int) $this->db->lastInsertId();
        }

        return $ok;
    }

    public function read(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE product_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET category_id = :cat, product_name = :name, description = :desc,
                 price = :price, stock_quantity = :stock, is_available = :avail
             WHERE product_id = :id'
        );

        return $stmt->execute([
            'cat' => $data['category_id'],
            'name' => $data['product_name'],
            'desc' => $data['description'] ?? '',
            'price' => $data['price'],
            'stock' => $data['stock_quantity'],
            'avail' => $data['is_available'] ?? 1,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE product_id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Searchable / filterable / sortable listing for the catalog and admin pages.
     * Demonstrates WHERE, ORDER BY, and LIMIT as required.
     */
    public static function findAvailable(
        PDO $db,
        string $search = '',
        int $categoryId = 0,
        string $sort = 'name_asc',
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = 'SELECT p.*, c.category_name
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE p.is_available = 1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND p.product_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $sortMap = [
            'name_asc' => 'p.product_name ASC',
            'name_desc' => 'p.product_name DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
        ];
        $sql .= ' ORDER BY ' . ($sortMap[$sort] ?? $sortMap['name_asc']);
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

    public static function countAvailable(PDO $db, string $search = '', int $categoryId = 0): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM products p WHERE p.is_available = 1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND p.product_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public static function findAllForAdmin(
        PDO $db,
        string $search = '',
        int $categoryId = 0,
        string $sort = 'name_asc',
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = 'SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND p.product_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $sortMap = [
            'name_asc' => 'p.product_name ASC',
            'name_desc' => 'p.product_name DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'stock_asc' => 'p.stock_quantity ASC',
            'stock_desc' => 'p.stock_quantity DESC',
        ];
        $sql .= ' ORDER BY ' . ($sortMap[$sort] ?? $sortMap['name_asc']);
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

    public static function countAllForAdmin(PDO $db, string $search = '', int $categoryId = 0): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM products p WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND p.product_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public static function lowStockThreshold(): int
    {
        return 10;
    }
}
