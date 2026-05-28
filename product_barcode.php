<?php
require_once 'config/db.php';

if (!isset($_GET['id'])) {
  die("Pilih barang terlebih dahulu.");
}

$id = (int)$_GET['id'];
$qty = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;

$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
  die("Barang tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak QR Code - <?= htmlspecialchars($product['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      body {
        background: white !important;
        margin: 0;
        padding: 0;
      }
    }

    .label-box {
      width: 50mm;
      /* Standar label barcode thermal */
      height: 30mm;
      border: 1px dashed #cbd5e1;
      margin: 8px;
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 4px;
      box-sizing: border-box;
      background: #fff;
      page-break-inside: avoid;
    }

    /* CSS tambahan agar QR Code & teks SKU pas di tengah */
    .qrcode-img canvas,
    .qrcode-img img {
      margin: 0 auto;
    }

    @media print {
      .label-box {
        border: none;
        margin: 0;
        padding: 2px;
      }
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-800 p-6 lg:p-10 min-h-screen">

  <div class="max-w-4xl mx-auto">
    <div class="no-print bg-white p-6 rounded-[1.25rem] shadow-sm border border-slate-100 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
      <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full opacity-50 pointer-events-none"></div>

      <div class="relative z-10">
        <h1 class="text-xl font-bold text-slate-800 flex items-center">
          <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mr-3 shadow-inner">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
          </div>
          Cetak Label QR Code
        </h1>
        <p class="text-slate-500 text-sm mt-1 ml-13 font-medium">Produk: <span class="text-slate-700 font-bold"><?= htmlspecialchars($product['name']) ?></span></p>
      </div>

      <form action="" method="GET" class="flex flex-wrap items-center gap-3 relative z-10 w-full md:w-auto">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl p-1 shadow-sm">
          <label class="text-xs font-bold text-slate-500 uppercase px-3">Jumlah:</label>
          <input type="number" name="qty" value="<?= $qty ?>" min="1" max="100" class="w-16 px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-center font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <button type="submit" class="text-indigo-600 hover:text-indigo-800 px-3 py-1.5 font-bold text-sm transition tooltip" title="Perbarui Jumlah">
            Update
          </button>
        </div>

        <button type="button" onclick="window.print()" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-md shadow-indigo-600/20 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
          </svg>
          Cetak
        </button>

        <a href="products.php" class="bg-white border border-slate-200 text-slate-600 px-4 py-2.5 rounded-xl font-bold hover:bg-slate-50 transition shadow-sm">Kembali</a>
      </form>
    </div>

    <div class="bg-white p-6 sm:p-10 rounded-2xl shadow-sm border border-slate-200 text-center print:shadow-none print:border-none print:p-0 print:bg-transparent min-h-[400px]">
      <div id="labels-container" class="flex flex-wrap justify-center print:block print:w-[30mm]">
        <?php for ($i = 0; $i < $qty; $i++): ?>
          <div class="label-box print:w-[50mm] print:h-[30mm] print:mb-2">
            <div class="text-[10px] font-extrabold text-slate-800 text-center truncate w-full mb-1 leading-tight tracking-tight uppercase">
              <?= htmlspecialchars(substr($product['name'], 0, 25)) ?><?= strlen($product['name']) > 25 ? '..' : '' ?>
            </div>

            <div class="qrcode-img" data-sku="<?= htmlspecialchars($product['sku']) ?>"></div>

            <div class="text-[8px] font-bold text-slate-700 mt-0.5 tracking-wider">
              <?= htmlspecialchars($product['sku']) ?>
            </div>

            <?php if ($product['size']): ?>
              <div class="text-[8px] font-bold text-slate-600 mt-0.5 uppercase border border-slate-300 px-1 rounded-sm">
                SIZE: <?= htmlspecialchars($product['size']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>

      <?php if ($qty == 0): ?>
        <div class="flex flex-col items-center justify-center py-10">
          <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
          </div>
          <p class="text-slate-400 font-medium text-sm">Masukkan jumlah label dan tekan Update</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    window.onload = function() {
      // Ambil semua element dengan class qrcode-img
      const qrContainers = document.querySelectorAll('.qrcode-img');

      qrContainers.forEach(function(container) {
        const skuText = container.getAttribute('data-sku');

        // Generate QR Code untuk setiap elemen loop
        new QRCode(container, {
          text: skuText,
          width: 55, // Ukuran lebar QR Code (Disesuaikan dengan tinggi label 30mm)
          height: 55, // Ukuran tinggi QR Code
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M // Level toleransi error medium
        });
      });
    }
  </script>
</body>

</html>