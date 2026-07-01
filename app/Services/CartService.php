<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $instance = 'default';
    protected ?string $lastRowid = null;
    protected int $defaultTax = 18;

    public function instance(string $name): self
    {
        $this->instance = $name;
        return $this;
    }

    protected function getCart(): Collection
    {
        $cart = Session::get("cart.{$this->instance}", collect());
        $collection = $cart instanceof Collection ? $cart : collect($cart);
        return $collection->map(function ($item) {
            return is_array($item) ? (object) $item : $item;
        });
    }

    public function add(mixed $id, ?string $name = null, ?int $qty = null, ?float $price = null, array $options = [], int $tax = 0): self
    {
        if (is_array($id))
        {
            return $this->addArray($id);
        }

        $cart = $this->getCart();
        $rowId = md5((string)$id . serialize($options));

        $cartItem = (object) [
            'rowId' => $rowId,
            'id' => $id,
            'name' => $name,
            'qty' => $qty,
            'price' => (float) $price,
            'options' => $options,
            'tax' => $tax != null ? $tax : $this->defaultTax,
            'subtotal' => $qty * $price,
            'associatedModel' => null
        ];

        $cart->put($rowId, $cartItem);
        $this->lastRowid = $rowId;

        Session::put("cart.{$this->instance}", $cart);

        return $this;
    }

    protected function addArray(array $data): self
    {
        return $this->add(
            $data['id'],
            $data['name'] ?? null,
            (int)($data['qty'] ?? 1),
            (float)($data['price'] ?? 0),
            $data['options'] ?? [],
            (int)($data['tax'] ?? 0)
        );
    }

    public function associate(string $model): self
    {
        if (!$this->lastRowid) {
            return $this;
        }
                
        $cart = $this->getCart();   
        $item = $cart->get($this->lastRowid);

        if ($item) {
            $item->associatedModel = $model;
            $cart->put($this->lastRowid, $item);
            Session::put("cart.{$this->instance}", $cart);
        }

        return $this;
    }

    public function model(string $rowId): ?object
    {
        $item = $this->getCart()->get($rowId);

        if ($item && $item->associatedModel) {
            return $item->associatedModel::find($item->id);
        }

        return null;
    }

    public function content(): Collection
    {
        return $this->getCart();
    }

    public function get(string $rowId): ?object
    {
        return $this->getCart()->get($rowId);
    }

    public function update(string $rowId, mixed $data): object|bool
    {
        $cart = $this->getCart();
        if (!$cart->has($rowId)) return false;

        $item = $cart->get($rowId);

        if (is_numeric($data)) {
            $item->qty = (int)$data;
        }
        else {
            foreach ($data as $key => $value) {
                $item->$key = $value;
            }
        }

        $item->subtotal = $item->qty * $item->price;
        Session::put("cart.{$this->instance}", $cart);

        return $item;
    }

    public function remove(string $rowId): void
    {
        $cart = $this->getCart();
        $cart->forget($rowId);
        Session::put("cart.{$this->instance}", $cart);
    }

    public function destroy(): void
    {
        Session::forget("cart.{$this->instance}");
    }

    public function subtotal(): float
    {
        return (float) $this->getCart()->sum('subtotal');
    }

    public function taxTotal(): float
    {
        return (float) $this->getCart()->sum(function ($item) {
            return ($item->price * $item->qty) * ($item->tax / 100);
        });
    }

    public function total(): float
    {
        return $this->subtotal() + $this->taxTotal();
    }

    public function count(): int
    {
        return (int) $this->getCart()->sum('qty');
    }

    // public function has(string $rowId): bool
    // {
    //     return $this->getCart()->has($rowId);
    // }

    // public function empty(): bool
    // {
    //     return $this->getCart()->isEmpty();
    // }
}