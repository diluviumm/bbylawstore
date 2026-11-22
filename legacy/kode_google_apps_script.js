/**
 * Google Apps Script untuk Database Bbylaa Store
 * Catatan:
 * 1. Buka https://sheets.google.com -> Buat Spreadsheet baru bernama 'Database Bbylaa Store'.
 * 2. Buat header di baris 1 (kolom A - I):
 *    Timestamp | Kode Order | Kategori | Nama Produk | Varian | Harga | Nama Pembeli | No WhatsApp | Data Game / Catatan
 * 3. Buka menu Extensions (Ekstensi) -> Apps Script -> Paste kode ini.
 * 4. Klik Deploy -> New deployment -> Web app -> Who has access: Anyone -> Deploy.
 * 5. Salin Web App URL-nya, lalu paste ke variabel 'googleSheetUrl' di file index.html.
 */

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.tryLock(10000);

  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    var data = JSON.parse(e.postData.contents);

    var timestamp = new Date();
    var formattedDate = Utilities.formatDate(timestamp, "Asia/Makassar", "yyyy-MM-dd HH:mm:ss");

    // Menambahkan baris transaksi baru ke Google Sheets
    sheet.appendRow([
      formattedDate,
      data.orderId || '',
      data.category || '',
      data.productName || '',
      data.variant || '',
      data.price || 0,
      data.customerName || '',
      data.customerPhone || '',
      data.notes || ''
    ]);

    return ContentService
      .createTextOutput(JSON.stringify({ status: 'success', message: 'Data pesanan berhasil dicatat!' }))
      .setMimeType(ContentService.MimeType.JSON);

  } catch (error) {
    return ContentService
      .createTextOutput(JSON.stringify({ status: 'error', message: error.toString() }))
      .setMimeType(ContentService.MimeType.JSON);
  } finally {
    lock.releaseLock();
  }
}
