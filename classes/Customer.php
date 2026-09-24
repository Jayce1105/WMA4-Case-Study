<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../interfaces/CRUDInterface.php';

class Customer extends Model implements CRUDInterface
{
    private ?int $customerId = null;
    private string $fullName = '';
    private string $email = '';
    private string $phone = '';
    private string $address = '';

    public function getId(): ?int
    {
        return $this->customerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setFullName(string $name): void
    {
        $this->fullName = trim($name);
    }

    public function setEmail(string $email): void
    {
        $this->email = trim($email);
    }

    public function setPhone(string $phone): void
    {
        $this->phone = trim($phone);
    }

    public function setAddress(string $address): void
    {
        $this->address = trim($address);
    }

    public function validate(): bool
    {
        return $this->fullName !== ''
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false
            && $this->address !== '';
    }

    public function findOrCreate(): int
    {
        $stmt = $this->db->prepare('SELECT customer_id FROM customers WHERE email = :email');
        $stmt->execute(['email' => $this->email]);
        $row = $stmt->fetch();

        if ($row) {
            $this->customerId = (int) $row['customer_id'];
            return $this->customerId;
        }

        $this->create();
        return (int) $this->customerId;
    }

    public function create(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO customers (full_name, email, phone, address, password_hash)
             VALUES (:name, :email, :phone, :address, :pwd)'
        );
  
        $ok = $stmt->execute([
            'name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'pwd' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
        ]);

        if ($ok) {
            $this->customerId = (int) $this->db->lastInsertId();
        }

        return $ok;
    }

    public function read(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE customer_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE customers SET full_name = :name, phone = :phone, address = :address WHERE customer_id = :id'
        );

        return $stmt->execute([
            'name' => $data['full_name'],
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM customers WHERE customer_id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
