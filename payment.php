<?php
require_once "helpers.php";

$postId = isset($_GET["post_id"]) ? (int) $_GET["post_id"] : 0;
if ($postId <= 0) {
    set_flash("error", "Invalid payment request.");
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT posts.id, posts.title, posts.price, posts.post_type, users.name AS author_name
    FROM posts
    JOIN users ON users.id = posts.user_id
    WHERE posts.id = ?
");
$stmt->bind_param("i", $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    set_flash("error", "Post not found for payment.");
    header("Location: index.php");
    exit;
}

$paymentSuccess = null;
$paymentError = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $method = $_POST["payment_method"] ?? "";
    $name = trim($_POST["payer_name"] ?? "");
    $email = trim($_POST["payer_email"] ?? "");

    if ($name === "" || $email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $paymentError = "Please provide valid name and email.";
    } elseif (!in_array($method, ["mobile", "card", "bank"], true)) {
        $paymentError = "Please select a payment method.";
    } else {
        if ($method === "mobile") {
            $mobileProvider = $_POST["mobile_provider"] ?? "";
            $mobileNumber = trim($_POST["mobile_number"] ?? "");
            if (!in_array($mobileProvider, ["bkash", "rocket", "nagad"], true) || $mobileNumber === "") {
                $paymentError = "Please enter mobile payment details.";
            }
        } elseif ($method === "card") {
            $cardNumber = trim($_POST["card_number"] ?? "");
            $cardExpiry = trim($_POST["card_expiry"] ?? "");
            if ($cardNumber === "" || $cardExpiry === "") {
                $paymentError = "Please enter card details.";
            }
        } elseif ($method === "bank") {
            $bankName = trim($_POST["bank_name"] ?? "");
            $accountNumber = trim($_POST["account_number"] ?? "");
            if ($bankName === "" || $accountNumber === "") {
                $paymentError = "Please enter bank transfer details.";
            }
        }
    }

    if ($paymentError === null) {
        $paymentSuccess = "Payment successful";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .method-card { border: 1px solid #dee2e6; border-radius: 12px; padding: 14px; cursor: pointer; }
        .method-card.active { border-color: #0d6efd; background: #f7fbff; }
    </style>
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">Payment Checkout </h4>
                    <p class="mb-1"><strong>Post:</strong> <?= e($post["title"]) ?></p>
                    <p class="mb-1"><strong>By:</strong> <?= e($post["author_name"]) ?></p>
                    <p class="mb-0"><strong>Amount:</strong> $<?= e(number_format((float) ($post["price"] ?? 0), 2)) ?></p>
                </div>
            </div>

            <?php if ($paymentSuccess): ?>
                <div class="alert alert-success"><?= e($paymentSuccess) ?></div>
            <?php endif; ?>
            <?php if ($paymentError): ?>
                <div class="alert alert-danger"><?= e($paymentError) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Your Name</label>
                                <input type="text" name="payer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Your Email</label>
                                <input type="email" name="payer_email" class="form-control" required>
                            </div>
                        </div>

                        <label class="form-label">Choose Payment Method</label>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="method-card w-100" data-method="mobile">
                                    <input type="radio" name="payment_method" value="mobile" class="form-check-input me-2">
                                    bKash / Rocket / Nagad
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="method-card w-100" data-method="card">
                                    <input type="radio" name="payment_method" value="card" class="form-check-input me-2">
                                    Card Payment
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="method-card w-100" data-method="bank">
                                    <input type="radio" name="payment_method" value="bank" class="form-check-input me-2">
                                    Bank Transfer
                                </label>
                            </div>
                        </div>

                        <div id="mobileFields" class="border rounded-3 p-3 mb-3 d-none">
                            <h6>Mobile Payment Details</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Provider</label>
                                    <select name="mobile_provider" class="form-select">
                                        <option value="">Select provider</option>
                                        <option value="bkash">bKash</option>
                                        <option value="rocket">Rocket</option>
                                        <option value="nagad">Nagad</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="text" name="mobile_number" class="form-control" placeholder="01XXXXXXXXX">
                                </div>
                            </div>
                        </div>

                        <div id="cardFields" class="border rounded-3 p-3 mb-3 d-none">
                            <h6>Card Payment Details</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Card Number</label>
                                    <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Expiry</label>
                                    <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY">
                                </div>
                            </div>
                        </div>

                        <div id="bankFields" class="border rounded-3 p-3 mb-3 d-none">
                            <h6>Bank Transfer Details</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" name="account_number" class="form-control">
                                </div>
                            </div>
                        </div>

                        

                        <button class="btn btn-success">Confirm Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
<script>
    (function () {
        const radios = document.querySelectorAll('input[name="payment_method"]');
        const cards = document.querySelectorAll(".method-card");
        const mobileFields = document.getElementById("mobileFields");
        const cardFields = document.getElementById("cardFields");
        const bankFields = document.getElementById("bankFields");

        function toggleMethod(method) {
            mobileFields.classList.toggle("d-none", method !== "mobile");
            cardFields.classList.toggle("d-none", method !== "card");
            bankFields.classList.toggle("d-none", method !== "bank");
            cards.forEach((card) => card.classList.toggle("active", card.dataset.method === method));
        }

        radios.forEach((radio) => {
            radio.addEventListener("change", () => toggleMethod(radio.value));
        });
    })();
</script>
</body>
</html>
