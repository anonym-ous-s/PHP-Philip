<?php

session_start();

$prices_base = [
    'BTC' => 30000.00,
    'ETH' => 2000.00,
    'ADA' => 0.50
];

$feePercent = 0.5;      
$minPurchase = 1.00;   
$maxPurchase = 10000.00;

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Andi',
        'balance' => 5000.00
    ];
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$prices = [];
foreach ($prices_base as $k => $v) {
    $randPct = (rand(-200, 200) / 10000.0); 
    $prices[$k] = round($v * (1 + $randPct), 8);
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset_history') {
        $_SESSION['history'] = [];
        $success = "Riwayat transaksi telah di-reset.";
    } elseif ($_POST['action'] === 'reset_balance') {
        $_SESSION['user']['balance'] = 5000.00;
        $success = "Saldo di-reset ke \$5,000.00.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy']) && $_POST['buy'] == '1') {
    
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request (CSRF).";
    } else {
        $mode = ($_POST['mode'] ?? 'fiat'); 
        $crypto = strtoupper(trim($_POST['crypto'] ?? ''));
        $fiatAmount = isset($_POST['fiat_amount']) ? (float) $_POST['fiat_amount'] : 0.0;
        $cryptoAmount = isset($_POST['crypto_amount']) ? (float) $_POST['crypto_amount'] : 0.0;
        $feePercentServer = isset($_POST['fee_percent']) ? (float) $_POST['fee_percent'] : $feePercent;

        if (!isset($prices[$crypto])) {
            $error = "Crypto '$crypto' tidak didukung.";
        } else {
            $pricePerUnit = (float) $prices[$crypto];

            if ($mode === 'crypto') {
                if ($cryptoAmount <= 0) {
                    $error = "Jumlah crypto harus lebih besar dari 0.";
                } else {
                    $fiatAmount = $cryptoAmount * $pricePerUnit;
                }
            } else {

                if ($fiatAmount <= 0) {
                    $error = "Jumlah fiat harus lebih besar dari 0.";
                }
            }

            if (!$error) {
                if ($fiatAmount < $minPurchase) {
                    $error = "Minimal pembelian adalah \$" . number_format($minPurchase, 2) . ".";
                } elseif ($fiatAmount > $maxPurchase) {
                    $error = "Maksimal pembelian adalah \$" . number_format($maxPurchase, 2) . ".";
                } else {

                    $fee = ($feePercentServer / 100.0) * $fiatAmount;
                    $totalCost = $fiatAmount + $fee;

                    if ($totalCost > $_SESSION['user']['balance'] + 0.000001) {
                        $error = "Saldo tidak cukup. Saldo: \$" . number_format($_SESSION['user']['balance'],2)
                            . " | Total biaya: \$" . number_format($totalCost,2);
                    } else {
                        $cryptoQty = $fiatAmount / $pricePerUnit;

                        $_SESSION['user']['balance'] = round($_SESSION['user']['balance'] - $totalCost, 8);

                        $tx = [
                            'time' => date('Y-m-d H:i:s'),
                            'crypto' => $crypto,
                            'price_per_unit' => $pricePerUnit,
                            'fiat_spent' => round($fiatAmount, 8),
                            'fee' => round($fee, 8),
                            'total_cost' => round($totalCost, 8),
                            'crypto_qty' => round($cryptoQty, 8)
                        ];
                        array_unshift($_SESSION['history'], $tx); 

                        $success = "Pembelian berhasil: {$tx['crypto_qty']} {$crypto} dibeli seharga \$" . number_format($tx['total_cost'],2) . " (fee \$" . number_format($tx['fee'],2) . ").";
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Sistem Pembelian Crypto — Simulasi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
    /* Modern, elegant CSS */
    :root {
        --bg: #0f1724;
        --card: #0b1220;
        --muted: #9aa7b2;
        --accent: #6ee7b7;
        --accent-2: #7dd3fc;
        --glass: rgba(255, 255, 255, 0.03);
        --radius: 14px;
        --maxwidth: 960px;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }

    * {
        box-sizing: border-box
    }

    html,
    body {
        height: 100%
    }

    body {
        margin: 0;
        background: linear-gradient(180deg, #071026 0%, #0f1724 60%);
        color: #e6eef6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        padding: 40px 20px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 20px;
    }

    .container {
        width: 100%;
        max-width: var(--maxwidth);
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
    }

    @media (max-width:900px) {
        .container {
            grid-template-columns: 1fr;
            padding-bottom: 40px
        }
    }

    .card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: 0 8px 30px rgba(2, 6, 23, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    header.top {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
    }

    .brand {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #042028;
        font-size: 18px;
        box-shadow: 0 6px 20px rgba(110, 231, 183, 0.08);
    }

    h1 {
        font-size: 18px;
        margin: 0
    }

    p.lead {
        color: var(--muted);
        margin: 4px 0 0;
        font-size: 13px
    }

    .balance {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 14px;
        border-radius: 12px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
    }

    .balance .title {
        font-size: 12px;
        color: var(--muted)
    }

    .balance .amount {
        font-size: 20px;
        font-weight: 700
    }

    form .field {
        margin-bottom: 12px
    }

    label {
        display: block;
        font-size: 13px;
        color: var(--muted);
        margin-bottom: 6px
    }

    select,
    input[type=number],
    input[type=text] {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.04);
        color: inherit;
        outline: none;
        transition: all .18s ease;
        font-size: 14px;
    }

    input:focus,
    select:focus {
        box-shadow: 0 6px 20px rgba(125, 211, 252, 0.06);
        border-color: rgba(125, 211, 252, 0.12)
    }

    .row {
        display: flex;
        gap: 10px
    }

    .row .col {
        flex: 1
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 0;
        cursor: pointer;
        background: linear-gradient(90deg, var(--accent), var(--accent-2));
        color: #042028;
        font-weight: 700;
        box-shadow: 0 8px 28px rgba(110, 231, 183, 0.08);
        transition: transform .12s ease, box-shadow .12s ease;
    }

    .btn:hover {
        transform: translateY(-3px)
    }

    .btn.secondary {
        background: transparent;
        color: var(--accent-2);
        border: 1px solid rgba(125, 211, 252, 0.12);
        font-weight: 600;
    }

    .muted {
        color: var(--muted);
        font-size: 13px
    }

    .notice {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 12px
    }

    .notice.success {
        background: rgba(34, 197, 94, 0.08);
        color: #a7f3d0;
        border: 1px solid rgba(34, 197, 94, 0.06)
    }

    .notice.error {
        background: rgba(248, 113, 113, 0.06);
        color: #ffcccc;
        border: 1px solid rgba(248, 113, 113, 0.06)
    }

    .prices {
        display: flex;
        gap: 10px;
        flex-wrap: wrap
    }

    .priceItem {
        background: var(--glass);
        padding: 10px;
        border-radius: 10px;
        min-width: 120px;
        border: 1px solid rgba(255, 255, 255, 0.02);
    }

    .priceItem small {
        display: block;
        color: var(--muted);
        font-size: 12px
    }

    .priceItem .p {
        font-weight: 700;
        font-size: 15px
    }

    table.history {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px
    }

    table.history th,
    table.history td {
        padding: 8px;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.03);
        font-size: 13px
    }

    table.history th {
        color: var(--muted);
        text-align: left;
        font-size: 12px
    }

    .footer-actions {
        display: flex;
        gap: 8px;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px
    }

    .small-muted {
        font-size: 12px;
        color: var(--muted)
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- LEFT: MAIN BUY CARD -->
        <div class="card">
            <header class="top">
                <div class="brand">CR</div>
                <div>
                    <h1>Sistem Pembelian Crypto (Simulasi)</h1>
                    <p class="lead">Beli crypto dengan USD — fitur live preview & riwayat transaksi.</p>
                </div>
            </header>

            <?php if ($error): ?>
            <div class="notice error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="notice success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div style="display:flex;gap:14px;align-items:center;margin-bottom:14px;">
                <div class="balance" style="flex:1">
                    <div class="title">User: <?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                    <div class="amount">$<span id="balance"><?= number_format($_SESSION['user']['balance'], 2) ?></span>
                    </div>
                    <div class="small-muted">Saldo saat ini</div>
                </div>

                <div class="balance" style="width:220px; text-align:right">
                    <div class="title">Fee default</div>
                    <div class="amount"><?= htmlspecialchars($feePercent) ?>%</div>
                    <div class="small-muted">Bisa diubah saat beli</div>
                </div>
            </div>

            <!-- Price strip -->
            <div class="prices" style="margin-bottom:16px">
                <?php foreach ($prices as $k => $p): ?>
                <div class="priceItem">
                    <small><?= $k ?></small>
                    <div class="p">$<?= number_format($p, 2) ?></div>
                    <div class="small-muted">Harga / unit</div>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="post" id="buyForm" novalidate>
                <input type="hidden" name="buy" value="1">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="field">
                    <label for="crypto">Pilih Aset</label>
                    <select name="crypto" id="crypto">
                        <?php foreach ($prices as $k => $p): ?>
                        <option value="<?= $k ?>"><?= $k ?> — $<?= number_format($p,2) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row" style="margin-bottom:12px">
                    <div class="col">
                        <label>Mode</label>
                        <div style="display:flex;gap:8px">
                            <button type="button" id="modeFiat" class="btn secondary" style="padding:8px 10px">Beli
                                pakai USD</button>
                            <button type="button" id="modeCrypto" class="btn secondary" style="padding:8px 10px">Beli
                                pakai Crypto</button>
                        </div>
                    </div>
                    <div class="col">
                        <label for="fee_percent">Fee (%)</label>
                        <input type="number" step="0.01" id="fee_percent" name="fee_percent"
                            value="<?= htmlspecialchars($feePercent) ?>">
                    </div>
                </div>

                <div id="fiatBlock">
                    <div class="field">
                        <label for="fiat_amount">Jumlah fiat (USD)</label>
                        <input type="number" step="0.01" id="fiat_amount" name="fiat_amount" placeholder="mis. 100.00"
                            value="">
                    </div>
                </div>

                <div id="cryptoBlock" style="display:none">
                    <div class="field">
                        <label for="crypto_amount">Jumlah Crypto (unit)</label>
                        <input type="number" step="0.00000001" id="crypto_amount" name="crypto_amount"
                            placeholder="mis. 0.005">
                    </div>
                </div>

                <div class="field">
                    <label>Preview Transaksi</label>
                    <div class="card" style="padding:12px;">
                        <div class="row">
                            <div class="col small-muted">Harga saat ini</div>
                            <div class="col" id="preview_price">$0.00</div>
                        </div>
                        <div class="row">
                            <div class="col small-muted">Fiat used</div>
                            <div class="col" id="preview_fiat">$0.00</div>
                        </div>
                        <div class="row">
                            <div class="col small-muted">Fee</div>
                            <div class="col" id="preview_fee">$0.00</div>
                        </div>
                        <div class="row">
                            <div class="col small-muted">Total Cost</div>
                            <div class="col" id="preview_total">$0.00</div>
                        </div>
                        <div class="row" style="margin-top:6px">
                            <div class="col small-muted">Anda akan menerima</div>
                            <div class="col" id="preview_crypto">0.00000000</div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:12px; display:flex; gap:10px;">
                    <button type="submit" class="btn">Beli Sekarang</button>
                    <button type="button" id="resetForm" class="btn secondary">Reset Form</button>
                </div>
            </form>

            <div class="footer-actions">
                <div class="small-muted">Minimal: $<?= number_format($minPurchase,2) ?> • Maksimal:
                    $<?= number_format($maxPurchase,2) ?></div>

                <div style="display:flex;gap:8px">
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="reset_history">
                        <button type="submit" class="btn secondary">Reset Riwayat</button>
                    </form>

                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="reset_balance">
                        <button type="submit" class="btn secondary">Reset Saldo</button>
                    </form>
                </div>
            </div>

            <hr style="margin:16px 0; border:none; border-top:1px solid rgba(255,255,255,0.03)">

            <h3 style="margin:0 0 8px 0">Riwayat Transaksi</h3>
            <?php if (empty($_SESSION['history'])): ?>
            <div class="small-muted">Belum ada transaksi.</div>
            <?php else: ?>
            <table class="history">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aset</th>
                        <th>Qty</th>
                        <th>Fiat</th>
                        <th>Fee</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['history'] as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['time']) ?></td>
                        <td><?= htmlspecialchars($h['crypto']) ?> @ $<?= number_format($h['price_per_unit'],2) ?></td>
                        <td><?= number_format($h['crypto_qty'],8) ?></td>
                        <td>$<?= number_format($h['fiat_spent'],2) ?></td>
                        <td>$<?= number_format($h['fee'],2) ?></td>
                        <td>$<?= number_format($h['total_cost'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <p class="small-muted" style="margin-top:12px">Catatan: ini simulasi. Untuk produksi gunakan API harga
                real-time, DB, otentikasi, dan perhitungan presisi (integer / bcmath).</p>
        </div>

        <!-- RIGHT: SUMMARY / INFO -->
        <aside class="card">
            <h3 style="margin-top:0">Ringkasan</h3>
            <p class="muted">Informasi singkat & tips</p>
            <ul class="small-muted">
                <li>Harga disimulasikan setiap reload.</li>
                <li>Gunakan mode "Beli pakai Crypto" jika ingin menukar jumlah koin langsung.</li>
                <li>Fee dapat diubah pada field Fee (%).</li>
            </ul>

            <div style="margin-top:12px;">
                <h4 style="margin:6px 0">Harga saat ini</h4>
                <?php foreach ($prices as $k => $p): ?>
                <div
                    style="display:flex;justify-content:space-between;padding:8px;background:var(--glass);border-radius:8px;margin-bottom:8px">
                    <div style="font-weight:700"><?= $k ?></div>
                    <div style="font-family:monospace">$<?= number_format($p,2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:12px;">
                <h4 style="margin:6px 0">Kontrol Cepat</h4>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <button id="copyBalance" class="btn secondary">Salin saldo</button>
                    <button id="simulatePrice" class="btn secondary">Simulasi perubahan harga</button>
                </div>
            </div>
        </aside>
    </div>

    <script>
    (function() {
        // Elements
        const prices = <?php echo json_encode($prices); ?>;
        const cryptoSelect = document.getElementById('crypto');
        const fiatInput = document.getElementById('fiat_amount');
        const cryptoInput = document.getElementById('crypto_amount');
        const preview_price = document.getElementById('preview_price');
        const preview_fiat = document.getElementById('preview_fiat');
        const preview_fee = document.getElementById('preview_fee');
        const preview_total = document.getElementById('preview_total');
        const preview_crypto = document.getElementById('preview_crypto');
        const feeInput = document.getElementById('fee_percent');
        const balanceEl = document.getElementById('balance');

        let mode = 'fiat'; // 'fiat' or 'crypto'
        const fiatBlock = document.getElementById('fiatBlock');
        const cryptoBlock = document.getElementById('cryptoBlock');

        // init preview
        function updatePreview() {
            const crypto = cryptoSelect.value;
            const price = parseFloat(prices[crypto]) || 0;
            const feePercent = parseFloat(feeInput.value) || 0;
            let fiat = 0;
            let cryptoQty = 0;

            if (mode === 'fiat') {
                fiat = parseFloat(fiatInput.value) || 0;
                let fee = (feePercent / 100.0) * fiat;
                let total = fiat + fee;
                cryptoQty = price > 0 ? (fiat / price) : 0;

                preview_price.textContent = '$' + price.toFixed(2);
                preview_fiat.textContent = '$' + fiat.toFixed(2);
                preview_fee.textContent = '$' + fee.toFixed(2);
                preview_total.textContent = '$' + total.toFixed(2);
                preview_crypto.textContent = cryptoQty.toFixed(8);
            } else {
                cryptoQty = parseFloat(cryptoInput.value) || 0;
                fiat = cryptoQty * price;
                let fee = (feePercent / 100.0) * fiat;
                let total = fiat + fee;

                preview_price.textContent = '$' + price.toFixed(2);
                preview_fiat.textContent = '$' + fiat.toFixed(2);
                preview_fee.textContent = '$' + fee.toFixed(2);
                preview_total.textContent = '$' + total.toFixed(2);
                preview_crypto.textContent = cryptoQty.toFixed(8);
            }
        }

        // Event listeners
        cryptoSelect.addEventListener('change', updatePreview);
        fiatInput.addEventListener('input', updatePreview);
        cryptoInput.addEventListener('input', updatePreview);
        feeInput.addEventListener('input', updatePreview);

        document.getElementById('modeFiat').addEventListener('click', function() {
            mode = 'fiat';
            fiatBlock.style.display = '';
            cryptoBlock.style.display = 'none';
            this.classList.remove('secondary');
            document.getElementById('modeCrypto').classList.remove('btn');
            document.getElementById('modeCrypto').classList.add('secondary');
            updatePreview();
        });
        document.getElementById('modeCrypto').addEventListener('click', function() {
            mode = 'crypto';
            fiatBlock.style.display = 'none';
            cryptoBlock.style.display = '';
            this.classList.remove('secondary');
            document.getElementById('modeFiat').classList.remove('btn');
            document.getElementById('modeFiat').classList.add('secondary');
            updatePreview();
        });

        document.getElementById('resetForm').addEventListener('click', function() {
            fiatInput.value = '';
            cryptoInput.value = '';
            feeInput.value = '<?php echo $feePercent; ?>';
            cryptoSelect.selectedIndex = 0;
            mode = 'fiat';
            fiatBlock.style.display = '';
            cryptoBlock.style.display = 'none';
            updatePreview();
        });

        // Copy balance
        document.getElementById('copyBalance').addEventListener('click', function() {
            navigator.clipboard.writeText(balanceEl.textContent.trim()).then(function() {
                alert('Saldo disalin: $' + balanceEl.textContent.trim());
            });
        });

        // simulate small price movement on client
        document.getElementById('simulatePrice').addEventListener('click', function() {
            for (let k in prices) {
                let pct = (Math.random() * 4 - 2) / 100.0; // -2%..+2%
                prices[k] = (parseFloat(prices[k]) * (1 + pct)).toFixed(8);
            }
            // refresh price labels
            updatePreview();
            alert('Harga disimulasikan berubah. Reload halaman untuk harga server baru.');
        });

        // initial
        updatePreview();

        // Form submit client validation: ensure min / max / balance check (friendly)
        document.getElementById('buyForm').addEventListener('submit', function(e) {
            const fiatVal = parseFloat(fiatInput.value) || 0;
            const cryptoVal = parseFloat(cryptoInput.value) || 0;
            const feePercentVal = parseFloat(feeInput.value) || 0;
            const selectedPrice = parseFloat(prices[cryptoSelect.value]) || 0;
            const balance = parseFloat(balanceEl.textContent.replace(/,/g, '')) || 0;

            let fiatUsed = 0;
            if (mode === 'fiat') {
                fiatUsed = fiatVal;
                if (fiatUsed <= 0) {
                    alert('Masukkan jumlah fiat yang valid.');
                    e.preventDefault();
                    return;
                }
            } else {
                if (cryptoVal <= 0) {
                    alert('Masukkan jumlah crypto yang valid.');
                    e.preventDefault();
                    return;
                }
                fiatUsed = cryptoVal * selectedPrice;
            }

            // min/max (match server config)
            const min = <?php echo $minPurchase; ?>;
            const max = <?php echo $maxPurchase; ?>;
            if (fiatUsed < min || fiatUsed > max) {
                alert('Jumlah tidak masuk batas: minimal $' + min + ' hingga maksimal $' + max);
                e.preventDefault();
                return;
            }

            const fee = (feePercentVal / 100.0) * fiatUsed;
            const total = fiatUsed + fee;
            if (total > balance + 0.000001) {
                alert('Saldo tidak cukup. Total biaya: $' + total.toFixed(2) + ' | Saldo: $' + balance
                    .toFixed(2));
                e.preventDefault();
                return;
            }

            // set mode hidden field for server
            // inject a hidden input
            let existing = document.querySelector('input[name="mode"]');
            if (!existing) {
                const inpt = document.createElement('input');
                inpt.type = 'hidden';
                inpt.name = 'mode';
                inpt.value = mode;
                this.appendChild(inpt);
            } else {
                existing.value = mode;
            }
        });

    })();
    </script>
</body>

</html>