<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Exceptions\CartException;
use App\Http\Controllers\OrderController;
use App\Services\Cart\CartService;
use App\Services\CheckoutData;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentManager;
use App\Services\ShippingCalculator;
use App\Support\Provinces;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Checkout một trang: thông tin → giao hàng → thanh toán → tóm tắt. Phí ship và tổng tiền
 * tính lại mỗi khi khách đổi tỉnh/phương thức, để không có bất ngờ ở bước cuối.
 */
class CheckoutPage extends Component
{
    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerEmail = '';

    public string $provinceCode = '';

    public string $ward = '';

    public string $street = '';

    public string $note = '';

    public ?int $shippingMethodId = null;

    public string $paymentMethod = '';

    public ?int $savedAddressId = null;

    public bool $agree = true;

    public function mount(CartService $cart, PaymentManager $payments): mixed
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart.index');
        }

        $user = auth()->user();
        if ($user) {
            $this->customerName = $user->name;
            $this->customerPhone = (string) $user->phone;
            $this->customerEmail = (string) $user->email;

            $address = $user->addresses()->orderByDesc('is_default')->first();
            if ($address) {
                $this->useAddress($address->id);
            }
        }

        $this->paymentMethod = $payments->availableMethods()[0]->value ?? '';

        return null;
    }

    public function useAddress(int $addressId): void
    {
        $address = auth()->user()?->addresses()->find($addressId);
        if (! $address) {
            return;
        }

        $this->savedAddressId = $address->id;
        $this->customerName = $address->recipient_name;
        $this->customerPhone = $address->phone;
        $this->provinceCode = $address->province_code;
        $this->ward = $address->ward;
        $this->street = $address->street;
        $this->shippingMethodId = null;
    }

    public function updatedProvinceCode(): void
    {
        $this->shippingMethodId = null;
    }

    protected function rules(): array
    {
        return [
            'customerName' => ['required', 'string', 'max:100'],
            'customerPhone' => ['required', 'regex:/^(0|\+?84)[0-9]{9,10}$/'],
            'customerEmail' => ['nullable', 'email', 'max:255'],
            'provinceCode' => ['required', Rule::in(array_keys(Provinces::options()))],
            'ward' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'shippingMethodId' => ['required', 'integer'],
            'paymentMethod' => ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, app(PaymentManager::class)->availableMethods()))],
            'agree' => ['accepted'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'customerName' => 'họ tên',
            'customerPhone' => 'số điện thoại',
            'customerEmail' => 'email',
            'provinceCode' => 'tỉnh/thành phố',
            'ward' => 'xã/phường',
            'street' => 'số nhà, tên đường',
            'shippingMethodId' => 'phương thức vận chuyển',
            'paymentMethod' => 'phương thức thanh toán',
            'agree' => 'điều khoản',
        ];
    }

    protected function messages(): array
    {
        return ['customerPhone.regex' => 'Số điện thoại không đúng định dạng (VD: 0912345678).'];
    }

    public function placeOrder(CartService $cart, CheckoutService $checkout, PaymentManager $payments): mixed
    {
        $this->customerPhone = preg_replace('/\D/', '', $this->customerPhone);
        $this->validate();

        $method = PaymentMethod::from($this->paymentMethod);

        try {
            $order = $checkout->placeOrder($cart, new CheckoutData(
                customerName: trim($this->customerName),
                customerPhone: $this->customerPhone,
                customerEmail: $this->customerEmail ?: null,
                provinceCode: $this->provinceCode,
                ward: trim($this->ward),
                street: trim($this->street),
                note: $this->note ?: null,
                shippingMethodId: (int) $this->shippingMethodId,
                paymentMethod: $method,
                userId: auth()->id(),
                ipAddress: request()->ip(),
            ));
        } catch (CartException $e) {
            $this->addError('order', $e->getMessage());

            return null;
        }

        OrderController::grantAccess($order);

        if ($method->isOnlineGateway()) {
            try {
                return $this->redirect($payments->initiate($order, (string) request()->ip()));
            } catch (CartException $e) {
                session()->flash('error', $e->getMessage());
            }
        }

        return $this->redirectRoute('orders.show', $order);
    }

    public function render(CartService $cart, ShippingCalculator $shipping, PaymentManager $payments): View
    {
        $subtotal = $cart->subtotal();
        $coupon = $cart->coupon(auth()->id());
        $discount = $coupon?->discountFor($subtotal) ?? 0;

        $methods = $shipping->methodsFor($this->provinceCode ?: null);
        if ($this->provinceCode && ! $methods->contains('id', $this->shippingMethodId)) {
            $this->shippingMethodId = $methods->first()?->id;
        }

        $selected = $methods->firstWhere('id', $this->shippingMethodId);
        $shippingFee = $selected ? $shipping->fee($selected, $subtotal, $coupon) : null;

        return view('livewire.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'shippingMethods' => $methods,
            'shippingFee' => $shippingFee,
            'shippingCalculator' => $shipping,
            'total' => max(0, $subtotal - $discount + ($shippingFee ?? 0)),
            'paymentMethods' => $payments->availableMethods(),
            'provinces' => Provinces::options(),
            'addresses' => auth()->user()?->addresses()->orderByDesc('is_default')->get() ?? collect(),
        ]);
    }
}
