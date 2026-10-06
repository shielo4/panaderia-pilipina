<section class="section payment-section" id="payment" <?php echo $cart === [] ? 'hidden' : ''; ?>>
    <div class="section-heading"><h2>Payment</h2></div>
    <p class="customer-name"><strong>Customer:</strong> <?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php if ($paymentMessage !== ''): ?>
        <p class="payment-message"><?php echo $paymentMessage; ?></p>
    <?php endif; ?>
    <?php if ($cart === []): ?>
        <p class="empty-cart">Your cart is empty. Add bread to see the quantity and total here.</p>
    <?php else: ?>
        <table class="cart-table">
            <thead><tr><th>Bread</th><th>Quantity</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($cart as $product => $quantity): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product); ?></td>
                        <td><span class="quantity-controls"><button class="quantity-btn" type="button" data-product="<?php echo htmlspecialchars($product); ?>" data-change="-1" aria-label="Remove one <?php echo htmlspecialchars($product); ?>">-</button><strong><?php echo $quantity; ?></strong><button class="quantity-btn" type="button" data-product="<?php echo htmlspecialchars($product); ?>" data-change="1" aria-label="Add one <?php echo htmlspecialchars($product); ?>">+</button></span></td>
                        <td>PHP <?php echo number_format($products[$product] * $quantity, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cart-total">Total: PHP <?php echo number_format($cartTotal, 2); ?></p>
    <?php endif; ?>


    <form class="payment-form" method="POST">
        <div class="payment-field payment-methods-row">
            <label>Payment method</label>
            <div class="payment-options" id="payment_options">
                <button type="button" class="payment-option" data-value="Cash on Delivery">Cash on Delivery</button>
                <button type="button" class="payment-option" data-value="GCash">GCash</button>
                <button type="button" class="payment-option" data-value="Maya">Maya</button>
            </div>
            <input type="hidden" id="payment_method" name="payment_method" value="" required>
        </div>

        <div class="gcash-details" id="gcash-details">
            <div>
                <img id="payment_qr" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data=GCash%20Panaderia%20Pilipina%2009171234567" alt="Payment QR code for Panaderia Pilipina">
                <label class="gcash-number-label" id="number_label" for="gcash_number">Send money through number</label>
                <input class="gcash-number-input" type="tel" id="gcash_number" name="gcash_number" placeholder="Enter 11-digit GCash number" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" autocomplete="tel">
            </div>
            <div>
                <strong id="payment_title">Pay through GCash</strong>
                <p class="gcash-note" id="payment_note">Scan the QR code or enter your 11-digit number, then place your order.</p>
            </div>
        </div>

        <div class="payment-field" id="customer_address_wrapper" style="display:none;">
            <label for="customer_address">Delivery address</label>
            <textarea id="customer_address" name="customer_address" rows="4" placeholder="House number, street name, barangay, city, province, and landmark"></textarea>
        </div>

        <button class="pay-btn place-order-btn" type="submit" name="place_order" <?php echo $cart === [] ? 'disabled' : ''; ?>>Place Order</button>
    </form>
</section>
<script>
    const paymentMethod = document.getElementById('payment_method');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const gcashDetails = document.getElementById('gcash-details');
    const gcashNumber = document.getElementById('gcash_number');
    const paymentQrcode = document.getElementById('payment_qr');
    const paymentTitle = document.getElementById('payment_title');
    const paymentNote = document.getElementById('payment_note');
    const numberLabel = document.getElementById('number_label');
    const addressWrapper = document.getElementById('customer_address_wrapper');
    const customerAddress = document.getElementById('customer_address');

    function setSelectedOption(value) {
        paymentOptions.forEach((button) => {
            const isActive = button.dataset.value === value;
            button.classList.toggle('active', isActive);
        });
        paymentMethod.value = value;
        updatePaymentDetails();
    }

    paymentOptions.forEach((button) => {
        button.addEventListener('click', () => setSelectedOption(button.dataset.value));
    });

    gcashNumber.addEventListener('input', () => {
        gcashNumber.value = gcashNumber.value.replace(/\D/g, '').slice(0, 11);
    });

    function updatePaymentDetails() {
        const selected = paymentMethod.value;
        const isDigital = selected === 'GCash' || selected === 'Maya';
        const isCashOnDelivery = selected === 'Cash on Delivery';

        gcashDetails.classList.toggle('visible', isDigital || isCashOnDelivery);
        paymentQrcode.style.display = isDigital ? 'block' : 'none';
        gcashNumber.required = isDigital || isCashOnDelivery;
        gcashNumber.pattern = '[0-9]{11}';
        gcashNumber.minLength = 11;
        gcashNumber.maxLength = 11;

        if (selected === 'Maya') {
            paymentQrcode.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=Maya%20Panaderia%20Pilipina%2009171234567';
            paymentTitle.textContent = 'Pay through Maya';
            paymentNote.textContent = 'Scan the Maya QR code or enter your 11-digit Maya number, then place your order.';
            numberLabel.textContent = 'Send money through Maya number';
            gcashNumber.placeholder = 'Enter 11-digit Maya number';
        } else if (selected === 'GCash') {
            paymentQrcode.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=GCash%20Panaderia%20Pilipina%2009171234567';
            paymentTitle.textContent = 'Pay through GCash';
            paymentNote.textContent = 'Scan the GCash QR code or enter your 11-digit number, then place your order.';
            numberLabel.textContent = 'Send money through number';
            gcashNumber.placeholder = 'Enter 11-digit GCash number';
        } else if (selected === 'Cash on Delivery') {
            paymentQrcode.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=Cash%20on%20Delivery%20Panaderia%20Pilipina';
            paymentTitle.textContent = 'Cash on Delivery';
            paymentNote.textContent = 'Please enter your contact number so the rider can reach you when your order arrives.';
            numberLabel.textContent = 'Contact number';
            gcashNumber.placeholder = 'Enter your 11-digit contact number';
        } else {
            gcashDetails.classList.remove('visible');
            gcashNumber.required = false;
            paymentTitle.textContent = 'Pay through GCash';
            paymentNote.textContent = 'Scan the QR code or enter your 11-digit number, then place your order.';
            numberLabel.textContent = 'Send money through number';
            gcashNumber.placeholder = 'Enter 11-digit GCash number';
        }

        const showAddress = selected !== '';
        addressWrapper.style.display = showAddress ? 'block' : 'none';
        customerAddress.required = showAddress;
    }

    updatePaymentDetails();
</script>
